<?php
declare(strict_types=1);
namespace Settle\Model;

use Settle\Database;

final class Slideshow
{
    public static function blank(): array
    {
        return [
            'id'         => null,
            'media_id'   => null,
            'caption'    => '',
            'link_url'   => '',
            'sort_order' => 0,
            'is_active'  => 1,
        ];
    }

    /**
     * Fetch all slides (active + inactive) with their image data joined.
     * Used by the admin index. Public-side rendering filters by is_active.
     */
    public static function all(): array
    {
        return Database::query(
            'SELECT s.*,
                    m.filename     AS media_filename,
                    m.alt_text     AS media_alt,
                    m.original_name AS media_original_name,
                    m.width        AS media_width,
                    m.height       AS media_height
             FROM slideshow_slides s
             INNER JOIN media m ON m.id = s.media_id
             ORDER BY s.sort_order, s.id'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $row = Database::query(
            'SELECT s.*,
                    m.filename     AS media_filename,
                    m.alt_text     AS media_alt,
                    m.original_name AS media_original_name
             FROM slideshow_slides s
             INNER JOIN media m ON m.id = s.media_id
             WHERE s.id = :id',
            [':id' => $id]
        )->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        // New slides go to the end of the order.
        $maxOrder = (int)Database::query(
            'SELECT COALESCE(MAX(sort_order), 0) FROM slideshow_slides'
        )->fetchColumn();

        Database::query(
            'INSERT INTO slideshow_slides
                (media_id, caption, link_url, sort_order, is_active)
             VALUES (:mid, :cap, :url, :sort, :active)',
            [
                ':mid'    => (int)$data['media_id'],
                ':cap'    => $data['caption'] === '' ? null : $data['caption'],
                ':url'    => $data['link_url'] === '' ? null : $data['link_url'],
                ':sort'   => $maxOrder + 10,
                ':active' => (int)$data['is_active'],
            ]
        );
        return (int)Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::query(
            'UPDATE slideshow_slides
             SET media_id  = :mid,
                 caption   = :cap,
                 link_url  = :url,
                 is_active = :active
             WHERE id = :id',
            [
                ':mid'    => (int)$data['media_id'],
                ':cap'    => $data['caption'] === '' ? null : $data['caption'],
                ':url'    => $data['link_url'] === '' ? null : $data['link_url'],
                ':active' => (int)$data['is_active'],
                ':id'     => $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM slideshow_slides WHERE id = :id', [':id' => $id]);
    }

    public static function toggleActive(int $id): void
    {
        Database::query(
            'UPDATE slideshow_slides SET is_active = 1 - is_active WHERE id = :id',
            [':id' => $id]
        );
    }

    /**
     * Apply a new order. $idsInOrder is an array of slide IDs in the desired
     * display sequence. We use sort_order increments of 10 so future inserts
     * can squeeze in without a full renumber.
     *
     * Runs inside a transaction so a partial failure doesn't leave the order
     * half-applied.
     */
    public static function reorder(array $idsInOrder): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'UPDATE slideshow_slides SET sort_order = :sort WHERE id = :id'
            );
            $sort = 10;
            foreach ($idsInOrder as $id) {
                $stmt->execute([':sort' => $sort, ':id' => (int)$id]);
                $sort += 10;
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
