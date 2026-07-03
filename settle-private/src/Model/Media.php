<?php
declare(strict_types=1);
namespace Settle\Model;

use Settle\Database;

final class Media
{
    /**
     * Fetch one page of media items, optionally filtered to one album
     * (roadmap: Photo Albums) and/or a caption/original-name search term
     * (browsing thousands of items flat is unusable otherwise).
     *
     * @return array{items:array, total:int}
     */
    public static function paginate(int $page, int $perPage, ?int $albumId = null, ?string $search = null): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $joins  = 'LEFT JOIN users u ON u.id = m.uploaded_by';
        $wheres = [];
        $params = [];

        if ($albumId !== null && $albumId > 0) {
            $joins .= ' JOIN album_media am ON am.media_id = m.id AND am.album_id = :aid';
            $params[':aid'] = $albumId;
        }

        $search = trim((string) $search);
        if ($search !== '') {
            $wheres[]        = '(m.original_name LIKE :q OR m.caption LIKE :q OR m.alt_text LIKE :q)';
            $params[':q']     = '%' . $search . '%';
        }

        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';

        $items = Database::query(
            "SELECT m.*, u.display_name AS uploaded_by_name
             FROM media m
             $joins
             $whereSql
             ORDER BY m.uploaded_at DESC
             LIMIT " . (int)$perPage . ' OFFSET ' . (int)$offset,
            // LIMIT/OFFSET are forced to ints above; PDO won't bind them
            // as parameters with emulation disabled.
            $params
        )->fetchAll();

        $total = (int) Database::query(
            "SELECT COUNT(*) FROM media m $joins $whereSql",
            $params
        )->fetchColumn();

        return ['items' => $items, 'total' => $total];
    }

    public static function find(int $id): ?array
    {
        $row = Database::query('SELECT * FROM media WHERE id = :id', [':id' => $id])->fetch();
        return $row ?: null;
    }

    /**
     * Resolve pre-staged assets by their original_name, returned as a map
     * keyed by original_name. Lets a template reference a Library image by
     * a stable name (e.g. the homepage section backgrounds) without hard-
     * coding row IDs. Names that don't exist are simply absent from the map.
     *
     * @param array<int, string> $names
     * @return array<string, array> original_name => media row
     */
    public static function findByOriginalNames(array $names): array
    {
        $names = array_values(array_unique(array_filter(
            $names,
            static fn ($n): bool => is_string($n) && $n !== ''
        )));
        if ($names === []) {
            return [];
        }

        $placeholders = [];
        $params       = [];
        foreach ($names as $i => $name) {
            $key                = ':n' . $i;
            $placeholders[]     = $key;
            $params[$key]       = $name;
        }

        $rows = Database::query(
            'SELECT * FROM media WHERE original_name IN (' . implode(', ', $placeholders) . ')',
            $params
        )->fetchAll();

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['original_name']] = $row;
        }
        return $map;
    }

    public static function create(array $data, int $userId): int
    {
        Database::query(
            'INSERT INTO media
                (filename, thumbnail_filename, original_name, mime_type, file_size,
                 width, height, alt_text, caption, uploaded_by)
             VALUES
                (:filename, :thumbnail_filename, :original_name, :mime_type, :file_size,
                 :width, :height, :alt_text, :caption, :uid)',
            [
                ':filename'           => $data['filename'],
                ':thumbnail_filename' => $data['thumbnail_filename'] ?? null,
                ':original_name'      => $data['original_name'],
                ':mime_type'          => $data['mime_type'],
                ':file_size'          => $data['file_size'],
                ':width'              => $data['width'],
                ':height'             => $data['height'],
                ':alt_text'           => $data['alt_text'],
                ':caption'            => $data['caption'],
                ':uid'                => $userId,
            ]
        );
        return (int)Database::pdo()->lastInsertId();
    }

    public static function updateMetadata(int $id, string $altText, string $caption): void
    {
        Database::query(
            'UPDATE media SET alt_text = :alt, caption = :cap WHERE id = :id',
            [
                ':alt' => $altText === '' ? null : $altText,
                ':cap' => $caption === '' ? null : $caption,
                ':id'  => $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM media WHERE id = :id', [':id' => $id]);
    }

    /**
     * Image rows that have no thumbnail yet (roadmap #9 backfill). PDFs and
     * any non-image rows are excluded — they never get a thumbnail. Used only
     * by bin/thumbnail-backfill.php.
     *
     * @return array<int, array{id:int, filename:string}>
     */
    public static function imagesWithoutThumbnail(): array
    {
        return Database::query(
            "SELECT id, filename
             FROM media
             WHERE thumbnail_filename IS NULL
               AND mime_type LIKE 'image/%'
             ORDER BY id ASC"
        )->fetchAll();
    }

    /**
     * Record a generated thumbnail path against a media row (#9 backfill).
     */
    public static function setThumbnail(int $id, string $thumbnailFilename): void
    {
        Database::query(
            'UPDATE media SET thumbnail_filename = :thumb WHERE id = :id',
            [':thumb' => $thumbnailFilename, ':id' => $id]
        );
    }
}
