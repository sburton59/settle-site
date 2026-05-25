<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\Model\Staff;
use Settle\Model\Media;

final class StaffController extends BaseController
{
    public function index(): void
    {
        $staff = Staff::all();
        $this->render('admin/staff/index', [
            'staff' => $staff,
        ]);
    }

    public function create(): void
    {
        $this->render('admin/staff/edit', [
            'person' => Staff::blank(),
            'isNew'  => true,
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $data = $this->collectFormData();
        $errors = $this->validate($data);
        if ($errors) {
            $this->render('admin/staff/edit', [
                'person' => array_merge(Staff::blank(), $data),
                'isNew'  => true,
                'errors' => $errors,
            ]);
            return;
        }
        Staff::create($data);
        $this->flash('success', 'Staff member added.');
        $this->redirect('/admin/staff');
    }

    public function edit(array $params): void
    {
        $person = Staff::find((int)$params['id']);
        if (!$person) { http_response_code(404); echo 'Staff member not found.'; return; }
        $this->render('admin/staff/edit', [
            'person' => $person,
            'isNew'  => false,
            'errors' => [],
        ]);
    }

    public function update(array $params): void
    {
        $id = (int)$params['id'];
        $person = Staff::find($id);
        if (!$person) { http_response_code(404); echo 'Staff member not found.'; return; }

        $data = $this->collectFormData();
        $errors = $this->validate($data);
        if ($errors) {
            $this->render('admin/staff/edit', [
                'person' => array_merge($person, $data),
                'isNew'  => false,
                'errors' => $errors,
            ]);
            return;
        }
        Staff::update($id, $data);
        $this->flash('success', 'Staff member saved.');
        $this->redirect('/admin/staff');
    }

    public function destroy(array $params): void
    {
        Staff::delete((int)$params['id']);
        $this->flash('success', 'Staff member removed.');
        $this->redirect('/admin/staff');
    }

    public function toggle(array $params): void
    {
        Staff::toggleVisible((int)$params['id']);
        $this->flash('success', 'Staff member visibility updated.');
        $this->redirect('/admin/staff');
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
            echo json_encode(['ok' => false, 'error' => 'No staff IDs supplied.']);
            return;
        }

        try {
            Staff::reorder($ids);
            echo json_encode(['ok' => true]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Reorder failed.']);
        }
    }

    private function collectFormData(): array
    {
        $photoId = (int)$this->input('photo_media_id', 0);
        return [
            'full_name'      => trim((string)$this->input('full_name', '')),
            'title'          => trim((string)$this->input('title', '')),
            'email'          => trim((string)$this->input('email', '')),
            'phone'          => trim((string)$this->input('phone', '')),
            'bio_html'       => (string)$this->input('bio_html', ''),
            'photo_media_id' => $photoId > 0 ? $photoId : null,
            'is_visible'     => $this->input('is_visible') ? 1 : 0,
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];

        if ($data['full_name'] === '') {
            $errors['full_name'] = 'Full name is required.';
        } elseif (mb_strlen($data['full_name']) > 150) {
            $errors['full_name'] = 'Full name must be 150 characters or fewer.';
        }

        if (mb_strlen($data['title']) > 150) {
            $errors['title'] = 'Title must be 150 characters or fewer.';
        }

        if ($data['email'] !== '') {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'That doesn\'t look like a valid email address.';
            } elseif (mb_strlen($data['email']) > 190) {
                $errors['email'] = 'Email must be 190 characters or fewer.';
            }
        }

        if (mb_strlen($data['phone']) > 50) {
            $errors['phone'] = 'Phone number must be 50 characters or fewer.';
        }

        // Photo is optional, but if one is selected confirm it exists
        // and is actually an image.
        if (!empty($data['photo_media_id'])) {
            $media = Media::find((int)$data['photo_media_id']);
            if (!$media) {
                $errors['photo_media_id'] = 'That photo no longer exists in the Media Library.';
            } elseif (strpos((string)$media['mime_type'], 'image/') !== 0) {
                $errors['photo_media_id'] = 'Staff photos must be image files.';
            }
        }

        return $errors;
    }
}
