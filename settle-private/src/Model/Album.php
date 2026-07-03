<?php
declare(strict_types=1);
namespace Settle\Model;

use Settle\Database;

/**
 * Photo albums — the public gallery feature (Flickr replacement).
 *
 * photo_albums is a curated, editor-managed list (mirrors Category);
 * album_media is the many-to-many junction to the media table (mirrors
 * post_categories). A photo only appears in the public gallery once it is
 * explicitly assigned to a PUBLISHED album via album_media — nothing in
 * the Media Library shows up automatically.
 *
 * Cover image resolution (index + detail hero) always prefers the
 * explicit cover_media_id; if unset, falls back to the oldest-assigned
 * photo in the album; if the album has no photos at all, callers render
 * a placeholder (no query failure either way).
 */
final class Album
{
    public static function blank(): array
    {
        return [
            'id'             => null,
            'name'           => '',
            'slug'           => '',
            'description'    => '',
            'event_date'     => '',
            'cover_media_id' => null,
            'is_published'   => 0,
            'sort_order'     => 0,
        ];
    }

    /**
     * All albums for the admin list, newest event_date first, with a photo
     * count and resolved cover (explicit cover, else oldest-assigned photo).
     */
    public static function all(): array
    {
        return Database::query(
            "SELECT a.*,
                    (SELECT COUNT(*) FROM album_media am WHERE am.album_id = a.id) AS photo_count,
                    COALESCE(cover.filename, fallback_m.filename) AS cover_filename,
                    COALESCE(cover.thumbnail_filename, fallback_m.thumbnail_filename, fallback_m.filename) AS cover_thumbnail
             FROM photo_albums a
             LEFT JOIN media cover ON cover.id = a.cover_media_id
             LEFT JOIN album_media fallback_am ON fallback_am.media_id = (
                 SELECT am2.media_id FROM album_media am2
                  WHERE am2.album_id = a.id
                  ORDER BY am2.sort_order ASC, am2.added_at ASC, am2.media_id ASC
                  LIMIT 1
             ) AND fallback_am.album_id = a.id
             LEFT JOIN media fallback_m ON fallback_m.id = fallback_am.media_id
             ORDER BY a.event_date IS NULL, a.event_date DESC, a.created_at DESC"
        )->fetchAll();
    }

    /**
     * Published albums only, same shape as all(), for the public /photos
     * index. Newest event_date first (nulls last), matching the Flickr
     * reference layout.
     */
    public static function allPublished(): array
    {
        return Database::query(
            "SELECT a.*,
                    (SELECT COUNT(*) FROM album_media am WHERE am.album_id = a.id) AS photo_count,
                    COALESCE(cover.filename, fallback_m.filename) AS cover_filename,
                    COALESCE(cover.thumbnail_filename, fallback_m.thumbnail_filename, fallback_m.filename) AS cover_thumbnail
             FROM photo_albums a
             LEFT JOIN media cover ON cover.id = a.cover_media_id
             LEFT JOIN album_media fallback_am ON fallback_am.media_id = (
                 SELECT am2.media_id FROM album_media am2
                  WHERE am2.album_id = a.id
                  ORDER BY am2.sort_order ASC, am2.added_at ASC, am2.media_id ASC
                  LIMIT 1
             ) AND fallback_am.album_id = a.id
             LEFT JOIN media fallback_m ON fallback_m.id = fallback_am.media_id
             WHERE a.is_published = 1
             ORDER BY a.event_date IS NULL, a.event_date DESC, a.created_at DESC"
        )->fetchAll();
    }

    /** Lightweight list for the Media Library's "add to album" picker — id/name/slug only. */
    public static function allForPicker(): array
    {
        return Database::query(
            'SELECT id, name, slug FROM photo_albums ORDER BY event_date IS NULL, event_date DESC, name'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $row = Database::query('SELECT * FROM photo_albums WHERE id = :id', [':id' => $id])->fetch();
        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $row = Database::query(
            'SELECT * FROM photo_albums WHERE slug = :s LIMIT 1',
            [':s' => $slug]
        )->fetch();
        return $row ?: null;
    }

    public static function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT 1 FROM photo_albums WHERE slug = :s' . ($ignoreId ? ' AND id <> :id' : '');
        $params = [':s' => $slug];
        if ($ignoreId) {
            $params[':id'] = $ignoreId;
        }
        return (bool) Database::query($sql, $params)->fetchColumn();
    }

    public static function create(array $data, int $userId): int
    {
        Database::query(
            'INSERT INTO photo_albums
                (slug, name, description, event_date, cover_media_id, is_published, sort_order, created_by)
             VALUES
                (:slug, :name, :description, :event_date, :cover, :pub, :sort, :uid)',
            [
                ':slug'        => $data['slug'],
                ':name'        => $data['name'],
                ':description' => $data['description'] !== '' ? $data['description'] : null,
                ':event_date'  => $data['event_date'] !== '' ? $data['event_date'] : null,
                ':cover'       => $data['cover_media_id'] ?: null,
                ':pub'         => (int) $data['is_published'],
                ':sort'        => (int) $data['sort_order'],
                ':uid'         => $userId,
            ]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::query(
            'UPDATE photo_albums
                SET slug = :slug, name = :name, description = :description,
                    event_date = :event_date, cover_media_id = :cover,
                    is_published = :pub, sort_order = :sort
              WHERE id = :id',
            [
                ':slug'        => $data['slug'],
                ':name'        => $data['name'],
                ':description' => $data['description'] !== '' ? $data['description'] : null,
                ':event_date'  => $data['event_date'] !== '' ? $data['event_date'] : null,
                ':cover'       => $data['cover_media_id'] ?: null,
                ':pub'         => (int) $data['is_published'],
                ':sort'        => (int) $data['sort_order'],
                ':id'          => $id,
            ]
        );
    }

    /**
     * Delete an album. album_media rows cascade away automatically; the
     * underlying media rows (and any other album's assignment of them)
     * are untouched.
     */
    public static function delete(int $id): void
    {
        Database::query('DELETE FROM photo_albums WHERE id = :id', [':id' => $id]);
    }

    /**
     * Photos in one album, newest-added-last (matches upload order unless
     * sort_order has been set). Reuses the media row shape consumers
     * already expect (thumbnail_filename, alt_text, etc.).
     *
     * @return array{items:array, total:int}
     */
    public static function photos(int $albumId, int $page, int $perPage): array
    {
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $items = Database::query(
            'SELECT m.*
             FROM album_media am
             JOIN media m ON m.id = am.media_id
             WHERE am.album_id = :aid
             ORDER BY am.sort_order ASC, am.added_at ASC
             LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            [':aid' => $albumId]
        )->fetchAll();

        $total = (int) Database::query(
            'SELECT COUNT(*) FROM album_media WHERE album_id = :aid',
            [':aid' => $albumId]
        )->fetchColumn();

        return ['items' => $items, 'total' => $total];
    }

    /** Album ids a given photo currently belongs to — for the media edit checkboxes. */
    public static function idsForMedia(int $mediaId): array
    {
        $rows = Database::query(
            'SELECT album_id FROM album_media WHERE media_id = :mid',
            [':mid' => $mediaId]
        )->fetchAll(\PDO::FETCH_COLUMN);
        return array_map('intval', $rows);
    }

    /**
     * Replace one photo's album assignments with exactly the given set
     * (used by the Media edit form's album checkboxes).
     *
     * @param array<int, int> $albumIds
     */
    public static function syncForMedia(int $mediaId, array $albumIds): void
    {
        Database::query('DELETE FROM album_media WHERE media_id = :mid', [':mid' => $mediaId]);
        foreach (array_unique(array_map('intval', $albumIds)) as $albumId) {
            if ($albumId <= 0) {
                continue;
            }
            Database::query(
                'INSERT INTO album_media (album_id, media_id) VALUES (:aid, :mid)',
                [':aid' => $albumId, ':mid' => $mediaId]
            );
        }
    }

    /**
     * Bulk-assign a set of photos to one album (the Media Library's
     * multi-select "Add to album" action). Idempotent per photo —
     * skips a pairing that already exists rather than erroring.
     *
     * @param array<int, int> $mediaIds
     * @return int Number of new assignments made.
     */
    public static function addPhotos(int $albumId, array $mediaIds): int
    {
        $added = 0;
        foreach (array_unique(array_map('intval', $mediaIds)) as $mediaId) {
            if ($mediaId <= 0) {
                continue;
            }
            $exists = Database::query(
                'SELECT 1 FROM album_media WHERE album_id = :aid AND media_id = :mid',
                [':aid' => $albumId, ':mid' => $mediaId]
            )->fetchColumn();
            if ($exists) {
                continue;
            }
            Database::query(
                'INSERT INTO album_media (album_id, media_id) VALUES (:aid, :mid)',
                [':aid' => $albumId, ':mid' => $mediaId]
            );
            $added++;
        }
        return $added;
    }

    /** Remove one photo from one album (used on the album edit page). */
    public static function removePhoto(int $albumId, int $mediaId): void
    {
        Database::query(
            'DELETE FROM album_media WHERE album_id = :aid AND media_id = :mid',
            [':aid' => $albumId, ':mid' => $mediaId]
        );
    }

    /**
     * Turn a free-text name into a URL-safe slug. Mirrors Category::slugify.
     */
    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        return trim($text, '-');
    }
}
