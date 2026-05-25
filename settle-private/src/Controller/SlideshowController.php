<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\Model\Slideshow;
use Settle\Model\Media;

final class SlideshowController extends BaseController
{
    public function index(): void
    {
        $slides = Slideshow::all();
        $this->render('admin/slideshow/index', [
            'slides' => $slides,
        ]);
    }

    public function create(): void
    {
        $this->render('admin/slideshow/edit', [
            'slide'  => Slideshow::blank(),
            'isNew'  => true,
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $data = $this->collectFormData();
        $errors = $this->validate($data);
        if ($errors) {
            $this->render('admin/slideshow/edit', [
                'slide'  => array_merge(Slideshow::blank(), $data),
                'isNew'  => true,
                'errors' => $errors,
            ]);
            return;
        }
        Slideshow::create($data);
        $this->flash('success', 'Slide added.');
        $this->redirect('/admin/slideshow');
    }

    public function edit(array $params): void
    {
        $slide = Slideshow::find((int)$params['id']);
        if (!$slide) { http_response_code(404); echo 'Slide not found.'; return; }
        $this->render('admin/slideshow/edit', [
            'slide'  => $slide,
            'isNew'  => false,
            'errors' => [],
        ]);
    }

    public function update(array $params): void
    {
        $id = (int)$params['id'];
        $slide = Slideshow::find($id);
        if (!$slide) { http_response_code(404); echo 'Slide not found.'; return; }

        $data = $this->collectFormData();
        $errors = $this->validate($data);
        if ($errors) {
            $this->render('admin/slideshow/edit', [
                'slide'  => array_merge($slide, $data),
                'isNew'  => false,
                'errors' => $errors,
            ]);
            return;
        }
        Slideshow::update($id, $data);
        $this->flash('success', 'Slide saved.');
        $this->redirect('/admin/slideshow');
    }

    public function destroy(array $params): void
    {
        Slideshow::delete((int)$params['id']);
        $this->flash('success', 'Slide removed.');
        $this->redirect('/admin/slideshow');
    }

    public function toggle(array $params): void
    {
        Slideshow::toggleActive((int)$params['id']);
        $this->flash('success', 'Slide visibility updated.');
        $this->redirect('/admin/slideshow');
    }

    /**
     * Reorder endpoint, hit by the drag-and-drop UI via fetch().
     * Expects JSON body: { "ids": [12, 7, 3, 9] } in desired order.
     * Returns JSON: { "ok": true } or { "ok": false, "error": "..." }.
     */
    public function reorder(): void
    {
        header('Content-Type: application/json');

        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);

        if (!is_array($payload) || !isset($payload['ids']) || !is_array($payload['ids'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid payload.']);
            return;
        }

        // Sanitize: only positive integers, no duplicates.
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $payload['ids']),
            fn($i) => $i > 0
        )));

        if ($ids === []) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'No slide IDs supplied.']);
            return;
        }

        try {
            Slideshow::reorder($ids);
            echo json_encode(['ok' => true]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Reorder failed.']);
        }
    }

    private function collectFormData(): array
    {
        return [
            'media_id'  => (int)$this->input('media_id', 0),
            'caption'   => trim((string)$this->input('caption', '')),
            'link_url'  => trim((string)$this->input('link_url', '')),
            'is_active' => $this->input('is_active') ? 1 : 0,
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['media_id'])) {
            $errors['media_id'] = 'You must pick an image from the Media Library.';
        } else {
            // Confirm the media row exists and is an image.
            $media = Media::find($data['media_id']);
            if (!$media) {
                $errors['media_id'] = 'That image no longer exists in the Media Library.';
            } elseif (strpos((string)$media['mime_type'], 'image/') !== 0) {
                $errors['media_id'] = 'Slides must use image files only.';
            }
        }
        if (mb_strlen($data['caption']) > 255)
            $errors['caption'] = 'Caption must be 255 characters or fewer.';
        if ($data['link_url'] !== '' && !preg_match('#^(https?:)?//#', $data['link_url']) && $data['link_url'][0] !== '/')
            $errors['link_url'] = 'Link must start with http://, https://, or / (for a path on this site).';
        if (mb_strlen($data['link_url']) > 500)
            $errors['link_url'] = 'Link URL must be 500 characters or fewer.';
        return $errors;
    }
}
