<?php
declare(strict_types=1);
namespace Settle;

/**
 * Upload — file upload handling for the Media Library.
 *
 * Validates incoming uploads, sanitizes filenames, resizes large images,
 * and writes the result to public_html/Settle/uploads/YYYY/MM/.
 *
 * The class is stateless; everything goes through the static handle() method
 * which returns either a fully-populated metadata array ready for the DB,
 * or an error string describing what went wrong.
 */
final class Upload
{
    /** Max allowed upload size (bytes). 10 MB. */
    private const MAX_BYTES = 10 * 1024 * 1024;

    /** Long edge in pixels — anything larger gets resized down on upload. */
    private const MAX_DIMENSION = 2000;

    /** JPEG quality used when re-saving resized images. */
    private const JPEG_QUALITY = 85;

    /** PNG compression level (0=none, 9=max). */
    private const PNG_COMPRESSION = 6;

    /** WebP quality. */
    private const WEBP_QUALITY = 85;

    /**
     * MIME type => extension. Both are checked: the browser-supplied MIME,
     * AND the actual content as detected by finfo. Both must agree.
     */
    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    /**
     * Handle a single $_FILES entry.
     *
     * @param array $file  An entry from $_FILES — must have keys:
     *                     name, type, tmp_name, error, size
     * @return array{ok:true, data:array}|array{ok:false, error:string}
     *
     * The `data` array on success is shaped for direct insertion into the
     * `media` table — filename, original_name, mime_type, width, height,
     * file_size, alt_text (empty default), caption (empty default).
     */
    public static function handle(array $file, string $uploadRoot): array
    {
        // 1. Did PHP report an upload error?
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => self::phpUploadError((int)$file['error'])];
        }

        // 2. Sanity: is this actually an uploaded file?
        if (!is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'error' => 'File was not properly uploaded.'];
        }

        // 3. Size check.
        $size = (int)$file['size'];
        if ($size <= 0) {
            return ['ok' => false, 'error' => 'Uploaded file is empty.'];
        }
        if ($size > self::MAX_BYTES) {
            return ['ok' => false, 'error' => sprintf(
                'File is too large. Maximum is %d MB.', self::MAX_BYTES / 1024 / 1024
            )];
        }

        // 4. MIME detection from actual content (not just the browser's claim).
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($file['tmp_name']) ?: '';
        if (!isset(self::ALLOWED_TYPES[$detectedMime])) {
            return ['ok' => false, 'error' => 'File type is not allowed. Permitted types: JPEG, PNG, GIF, WebP, PDF.'];
        }
        $extension = self::ALLOWED_TYPES[$detectedMime];

        // 5. Build the storage path: uploads/YYYY/MM/<random>.<ext>
        $year  = date('Y');
        $month = date('m');
        $destDir = "$uploadRoot/$year/$month";
        if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            return ['ok' => false, 'error' => 'Could not create upload directory.'];
        }

        // Try up to a few times in case of a (statistically improbable) collision.
        $relativePath = '';
        $destPath = '';
        for ($i = 0; $i < 5; $i++) {
            $name = bin2hex(random_bytes(8)) . '.' . $extension;
            $relativePath = "$year/$month/$name";
            $destPath = "$uploadRoot/$relativePath";
            if (!file_exists($destPath)) break;
            $relativePath = '';
        }
        if ($relativePath === '') {
            return ['ok' => false, 'error' => 'Could not generate a unique filename.'];
        }

        // 6. Move the upload into place, then process.
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['ok' => false, 'error' => 'Could not save uploaded file.'];
        }

        // 7. For images, optionally resize. For PDF, just record what we have.
        $width = null;
        $height = null;
        if ($detectedMime !== 'application/pdf') {
            $resized = self::maybeResize($destPath, $detectedMime);
            if (!$resized['ok']) {
                @unlink($destPath);
                return ['ok' => false, 'error' => $resized['error']];
            }
            $width  = $resized['width'];
            $height = $resized['height'];
            // The file may have been re-saved (changing size); re-stat it.
            $size = (int)filesize($destPath);
        }

        return [
            'ok' => true,
            'data' => [
                'filename'      => $relativePath,
                'original_name' => self::sanitizeOriginalName((string)$file['name']),
                'mime_type'     => $detectedMime,
                'file_size'     => $size,
                'width'         => $width,
                'height'        => $height,
                'alt_text'      => null,
                'caption'       => null,
            ],
        ];
    }

    /**
     * Delete a file from disk given its relative path. Silent if missing —
     * we want delete to succeed even if the disk file was already removed
     * out-of-band; the DB row is still the source of truth.
     */
    public static function deleteFile(string $relativePath, string $uploadRoot): void
    {
        $path = "$uploadRoot/$relativePath";
        if (is_file($path)) @unlink($path);
    }

    /**
     * If the image is bigger than MAX_DIMENSION on its long edge, resize
     * it and overwrite the file. Either way, returns the final dimensions.
     */
    private static function maybeResize(string $path, string $mime): array
    {
        $info = @getimagesize($path);
        if (!$info) {
            return ['ok' => false, 'error' => 'Could not read image dimensions.'];
        }
        [$origW, $origH] = $info;
        $long = max($origW, $origH);

        if ($long <= self::MAX_DIMENSION) {
            return ['ok' => true, 'width' => $origW, 'height' => $origH];
        }

        $scale = self::MAX_DIMENSION / $long;
        $newW = (int)round($origW * $scale);
        $newH = (int)round($origH * $scale);

        $src = self::loadImage($path, $mime);
        if (!$src) {
            return ['ok' => false, 'error' => 'Could not load image for resizing.'];
        }
        $dst = imagecreatetruecolor($newW, $newH);

        // Preserve transparency for PNG/GIF/WebP.
        if (in_array($mime, ['image/png', 'image/gif', 'image/webp'], true)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        $saved = self::saveImage($dst, $path, $mime);
        imagedestroy($src);
        imagedestroy($dst);

        if (!$saved) {
            return ['ok' => false, 'error' => 'Could not save resized image.'];
        }

        return ['ok' => true, 'width' => $newW, 'height' => $newH];
    }

    private static function loadImage(string $path, string $mime)
    {
        switch ($mime) {
            case 'image/jpeg': return @imagecreatefromjpeg($path);
            case 'image/png':  return @imagecreatefrompng($path);
            case 'image/gif':  return @imagecreatefromgif($path);
            case 'image/webp': return @imagecreatefromwebp($path);
        }
        return null;
    }

    private static function saveImage($image, string $path, string $mime): bool
    {
        switch ($mime) {
            case 'image/jpeg': return imagejpeg($image, $path, self::JPEG_QUALITY);
            case 'image/png':  return imagepng($image, $path, self::PNG_COMPRESSION);
            case 'image/gif':  return imagegif($image, $path);
            case 'image/webp': return imagewebp($image, $path, self::WEBP_QUALITY);
        }
        return false;
    }

    /**
     * Strip directory components and dangerous characters from a user-supplied
     * filename. We keep it only for display purposes — the actual stored name
     * is randomly generated. Trim to 200 chars to keep the column happy.
     */
    private static function sanitizeOriginalName(string $name): string
    {
        // strip path components a user might have included
        $name = basename($name);
        // strip control characters
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? $name;
        return mb_substr(trim($name), 0, 200);
    }

    private static function phpUploadError(int $code): string
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File is too large.';
            case UPLOAD_ERR_PARTIAL:
                return 'File upload was interrupted. Please try again.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was selected.';
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
                return 'Server could not save the file. Please contact the administrator.';
            case UPLOAD_ERR_EXTENSION:
                return 'Upload blocked by a PHP extension.';
        }
        return 'An unknown error occurred during upload.';
    }
}
