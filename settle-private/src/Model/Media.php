<?php
declare(strict_types=1);
namespace Settle\Model;

use Settle\Database;

final class Media
{
    /**
     * Fetch one page of media items.
     * @return array{items:array, total:int}
     */
    public static function paginate(int $page, int $perPage): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $items = Database::query(
            'SELECT m.*, u.display_name AS uploaded_by_name
             FROM media m
             LEFT JOIN users u ON u.id = m.uploaded_by
             ORDER BY m.uploaded_at DESC
             LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset
            // LIMIT/OFFSET are forced to ints above; PDO won't bind them
            // as parameters with emulation disabled.
        )->fetchAll();

        $total = (int)Database::query('SELECT COUNT(*) FROM media')->fetchColumn();

        return ['items' => $items, 'total' => $total];
    }

    public static function find(int $id): ?array
    {
        $row = Database::query('SELECT * FROM media WHERE id = :id', [':id' => $id])->fetch();
        return $row ?: null;
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
