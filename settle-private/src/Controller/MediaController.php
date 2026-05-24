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
        Upload::deleteFile($media['filename'], $this->uploadRoot());
        $this->flash('success', 'File deleted.');
        $this->redirect('/admin/media');
    }

    /**
     * Absolute path to the public uploads directory.
     * Resolves relative to settle-private/ so it always points at the right
     * place regardless of where the front controller is invoked from.
     */
    private function uploadRoot(): string
    {
        return realpath(__DIR__ . '/../../../public_html/Settle/uploads')
            ?: (__DIR__ . '/../../../public_html/Settle/uploads');
    }
}
