<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\Auth;
use Settle\Model\Media;
use Settle\Upload;

final class MediaController extends BaseController
{
    /** Items per page in the library browser. */
    private const PER_PAGE = 24;

    /** Items in the editor's image picker — show more, fewer pages. */
    private const PICKER_LIMIT = 200;

    public function index(): void
    {
        $page = max(1, (int)$this->input('p', 1));
        $result = Media::paginate($page, self::PER_PAGE);

        $totalPages = (int)max(1, ceil($result['total'] / self::PER_PAGE));

        $this->render('admin/media/index', [
            'items'      => $result['items'],
            'total'      => $result['total'],
            'page'       => $page,
            'totalPages' => $totalPages,
        ]);
    }

    public function upload(): void
    {
        $file = $_FILES['file'] ?? null;
        if (!is_array($file)) {
            $this->flash('error', 'No file was uploaded.');
            $this->redirect('/admin/media');
            return;
        }

        $result = Upload::handle($file, $this->uploadRoot());
        if (!$result['ok']) {
            $this->flash('error', $result['error']);
            $this->redirect('/admin/media');
            return;
        }

        Media::create($result['data'], (int)$_SESSION['user_id']);
        $this->flash('success', 'File uploaded.');
        $this->redirect('/admin/media');
    }

    public function edit(array $params): void
    {
        $media = Media::find((int)$params['id']);
        if (!$media) { http_response_code(404); echo 'Image not found.'; return; }

        $this->render('admin/media/edit', [
            'media'  => $media,
            'errors' => [],
        ]);
    }

    public function update(array $params): void
    {
        $id = (int)$params['id'];
        $media = Media::find($id);
        if (!$media) { http_response_code(404); echo 'Image not found.'; return; }

        $altText = trim((string)$this->input('alt_text', ''));
        $caption = trim((string)$this->input('caption', ''));

        $errors = [];
        if (mb_strlen($altText) > 255) $errors['alt_text'] = 'Alt text must be 255 characters or fewer.';
        if (mb_strlen($caption) > 500) $errors['caption']  = 'Caption must be 500 characters or fewer.';
        if ($errors) {
            $this->render('admin/media/edit', [
                'media'   => array_merge($media, ['alt_text' => $altText, 'caption' => $caption]),
                'errors'  => $errors,
            ]);
            return;
        }

        Media::updateMetadata($id, $altText, $caption);
        $this->flash('success', 'Image details saved.');
        $this->redirect("/admin/media/$id/edit");
    }

    public function destroy(array $params): void
    {
        $id = (int)$params['id'];
        $media = Media::find($id);
        if (!$media) { $this->redirect('/admin/media'); return; }

        // Authors may only delete their own uploads. Editors/admins can delete any.
        if (!Auth::hasRole('editor') && (int)$media['uploaded_by'] !== (int)$_SESSION['user_id']) {
            http_response_code(403);
            echo 'You can only delete files you uploaded.';
            return;
        }

        Media::delete($id);
        $root = $this->uploadRoot();
        Upload::deleteFile($media['filename'], $root);
        // Remove the distinct thumbnail file too (skip when it just reuses the
        // original, i.e. thumbnail_filename === filename, or is absent).
        $thumb = (string)($media['thumbnail_filename'] ?? '');
        if ($thumb !== '' && $thumb !== (string)$media['filename']) {
            Upload::deleteFile($thumb, $root);
        }
        $this->flash('success', 'File deleted.');
        $this->redirect('/admin/media');
    }

    /**
     * Picker view rendered inside TinyMCE's modal iframe. Shows a flat grid of
     * recent images (no pagination — the editor user can navigate to the full
     * library if they need more). Renders WITHOUT the admin layout because the
     * iframe needs a clean standalone document.
     */
    public function picker(): void
    {
        $result = Media::paginate(1, self::PICKER_LIMIT);
        \Settle\View::render('admin/media/picker', [
            'items' => $result['items'],
        ]);   // no layout — picker.php is a standalone HTML document
    }

    /**
     * Receives a drag-drop or paste-image upload from the TinyMCE editor.
     * Returns JSON: { "location": "/uploads/..." } on success,
     *               { "error":    "..." } on failure.
     */
    public function uploadFromEditor(): void
    {
        header('Content-Type: application/json');

        $file = $_FILES['file'] ?? null;
        if (!is_array($file)) {
            http_response_code(400);
            echo json_encode(['error' => 'No file received.']);
            return;
        }

        $result = Upload::handle($file, $this->uploadRoot());
        if (!$result['ok']) {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
            return;
        }

        Media::create($result['data'], (int)$_SESSION['user_id']);
        echo json_encode([
            'location' => '/uploads/' . $result['data']['filename'],
        ]);
    }

    /**
     * Drag-and-drop / multi-file upload endpoint (roadmap #9). The admin
     * media uploader posts ONE file per request here (so a single bad file
     * doesn't sink the batch and each shows its own progress), with the CSRF
     * token in the X-CSRF-Token header (router-verified). Returns JSON.
     *
     * Success: { "ok": true, "id": N, "name": "...", "url": "/uploads/...",
     *            "thumbUrl": "/uploads/..." }
     * Failure: { "ok": false, "error": "..." }  (with a 4xx status)
     *
     * Authorization matches the standard form upload: any authenticated user
     * (author or higher) may add to the library, exactly as upload() allows.
     */
    public function uploadAjax(): void
    {
        header('Content-Type: application/json');

        $file = $_FILES['file'] ?? null;
        if (!is_array($file)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'No file received.']);
            return;
        }

        $result = Upload::handle($file, $this->uploadRoot());
        if (!$result['ok']) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $result['error']]);
            return;
        }

        $id   = Media::create($result['data'], (int)$_SESSION['user_id']);
        $full = '/uploads/' . ltrim((string)$result['data']['filename'], '/');
        $thumb = !empty($result['data']['thumbnail_filename'])
            ? '/uploads/' . ltrim((string)$result['data']['thumbnail_filename'], '/')
            : $full;

        echo json_encode([
            'ok'       => true,
            'id'       => $id,
            'name'     => $result['data']['original_name'],
            'url'      => $full,
            'thumbUrl' => $thumb,
        ]);
    }

    /**
     * Absolute path to the public uploads directory.
     */
    private function uploadRoot(): string
    {
        return realpath(__DIR__ . '/../../../public_html/Settle/uploads')
            ?: (__DIR__ . '/../../../public_html/Settle/uploads');
    }
}
