<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\AuditLog;
use Settle\Model\Album;
use Settle\Model\Media;

/**
 * Photo album management (Flickr-replacement gallery feature).
 *
 * Album CRUD is editor+ only (gated at the route), mirroring
 * CategoryController — albums are the curated, published-or-not list;
 * any author+ may assign existing photos into them from the Media
 * Library (see MediaController::bulkAssign()). Removing a photo from an
 * album (here) never deletes the underlying media row — it only drops
 * the album_media pairing.
 */
final class AlbumController extends BaseController
{
    /** Photos shown per page on the album edit screen. */
    private const PHOTOS_PER_PAGE = 60;

    public function index(): void
    {
        $this->render('admin/albums/index', [
            'albums' => Album::all(),
        ]);
    }

    public function create(): void
    {
        $this->render('admin/albums/edit', [
            'album'  => Album::blank(),
            'isNew'  => true,
            'errors' => [],
            'photos' => ['items' => [], 'total' => 0],
            'page'   => 1,
            'totalPages' => 1,
            'coverPreview' => ['url' => '', 'alt' => ''],
        ]);
    }

    public function store(): void
    {
        $data = $this->collectFormData();
        $errors = $this->validate($data, null);
        if ($errors) {
            $this->render('admin/albums/edit', [
                'album'  => array_merge(Album::blank(), $data),
                'isNew'  => true,
                'errors' => $errors,
                'photos' => ['items' => [], 'total' => 0],
                'page'   => 1,
                'totalPages' => 1,
                'coverPreview' => $this->coverPreview($data['cover_media_id']),
            ]);
            return;
        }

        $id = Album::create($data, (int) $_SESSION['user_id']);
        AuditLog::record('album.create', 'photo_album', $id, ['name' => $data['name']]);
        $this->flash('success', 'Album created. Add photos to it from the Media Library.');
        $this->redirect("/admin/albums/$id/edit");
    }

    public function edit(array $params): void
    {
        $id = (int) $params['id'];
        $album = Album::find($id);
        if (!$album) {
            http_response_code(404);
            echo 'Album not found.';
            return;
        }

        $page   = max(1, (int) $this->input('p', 1));
        $photos = Album::photos($id, $page, self::PHOTOS_PER_PAGE);
        $totalPages = (int) max(1, ceil($photos['total'] / self::PHOTOS_PER_PAGE));

        $this->render('admin/albums/edit', [
            'album'      => $album,
            'isNew'      => false,
            'errors'     => [],
            'photos'     => $photos,
            'page'       => $page,
            'totalPages' => $totalPages,
            'coverPreview' => $this->coverPreview($album['cover_media_id'] !== null ? (int) $album['cover_media_id'] : null),
        ]);
    }

    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $album = Album::find($id);
        if (!$album) {
            http_response_code(404);
            echo 'Album not found.';
            return;
        }

        $data = $this->collectFormData();
        $errors = $this->validate($data, $id);
        if ($errors) {
            $page   = max(1, (int) $this->input('p', 1));
            $photos = Album::photos($id, $page, self::PHOTOS_PER_PAGE);
            $this->render('admin/albums/edit', [
                'album'      => array_merge($album, $data),
                'isNew'      => false,
                'errors'     => $errors,
                'photos'     => $photos,
                'page'       => $page,
                'totalPages' => (int) max(1, ceil($photos['total'] / self::PHOTOS_PER_PAGE)),
                'coverPreview' => $this->coverPreview($data['cover_media_id']),
            ]);
            return;
        }

        Album::update($id, $data);
        AuditLog::record('album.update', 'photo_album', $id, ['name' => $data['name']]);
        $this->flash('success', 'Album saved.');
        $this->redirect("/admin/albums/$id/edit");
    }

    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $album = Album::find($id);
        if (!$album) {
            http_response_code(404);
            echo 'Album not found.';
            return;
        }

        Album::delete($id);
        AuditLog::record('album.delete', 'photo_album', $id, ['name' => $album['name']]);
        $this->flash('success', 'Album deleted. The photos themselves remain in the Media Library.');
        $this->redirect('/admin/albums');
    }

    /**
     * Remove one photo from this album (from the album edit page's photo
     * grid). Does not delete the media row — only the album_media pairing.
     */
    public function removePhoto(array $params): void
    {
        $albumId = (int) $params['id'];
        $mediaId = (int) $params['mediaId'];
        $album = Album::find($albumId);
        if (!$album) {
            http_response_code(404);
            echo 'Album not found.';
            return;
        }

        Album::removePhoto($albumId, $mediaId);
        AuditLog::record('album.photo_remove', 'photo_album', $albumId, ['media_id' => $mediaId]);
        $this->flash('success', 'Photo removed from album.');
        $this->redirect("/admin/albums/$albumId/edit");
    }

    // -------------------------------------------------------------------

    /**
     * Resolve a media id into { url, alt } for the cover-image picker
     * preview (mirrors the pattern in templates/admin/slideshow/edit.php).
     * Prefers the thumbnail variant; empty strings if unset/not found.
     *
     * @return array{url:string, alt:string}
     */
    private function coverPreview(?int $mediaId): array
    {
        if (!$mediaId) {
            return ['url' => '', 'alt' => ''];
        }
        $media = Media::find($mediaId);
        if (!$media) {
            return ['url' => '', 'alt' => ''];
        }
        $rel = !empty($media['thumbnail_filename']) ? $media['thumbnail_filename'] : $media['filename'];
        return [
            'url' => '/uploads/' . ltrim((string) $rel, '/'),
            'alt' => (string) ($media['alt_text'] ?? ''),
        ];
    }

    private function collectFormData(): array
    {
        $name = trim((string) $this->input('name', ''));
        $slug = trim((string) $this->input('slug', ''));
        if ($slug === '' && $name !== '') {
            $slug = Album::slugify($name);
        }
        return [
            'name'           => $name,
            'slug'           => $slug,
            'description'    => trim((string) $this->input('description', '')),
            'event_date'     => trim((string) $this->input('event_date', '')),
            'cover_media_id' => (int) $this->input('cover_media_id', 0) ?: null,
            'is_published'   => $this->input('is_published') ? 1 : 0,
            'sort_order'     => (int) $this->input('sort_order', 0),
        ];
    }

    private function validate(array $data, ?int $ignoreId): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors['name'] = 'Name is required.';
        } elseif (mb_strlen($data['name']) > 150) {
            $errors['name'] = 'Name must be 150 characters or fewer.';
        }

        if ($data['slug'] === '') {
            $errors['slug'] = 'Web address is required (it fills in automatically from the name).';
        } elseif (!preg_match('/^[a-z0-9\-]+$/', $data['slug'])) {
            $errors['slug'] = 'Web address may only contain lowercase letters, numbers, and hyphens.';
        } elseif (Album::slugExists($data['slug'], $ignoreId)) {
            $errors['slug'] = 'That web address is already used by another album.';
        }

        if (mb_strlen($data['description']) > 500) {
            $errors['description'] = 'Description must be 500 characters or fewer.';
        }

        if ($data['event_date'] !== '') {
            $d = \DateTime::createFromFormat('Y-m-d', $data['event_date']);
            if (!$d || $d->format('Y-m-d') !== $data['event_date']) {
                $errors['event_date'] = 'Enter a valid date.';
            }
        }

        if ($data['cover_media_id'] !== null && !Media::find($data['cover_media_id'])) {
            $errors['cover_media_id'] = 'Choose a cover image from the Media Library, or leave it unset.';
        }

        return $errors;
    }
}
