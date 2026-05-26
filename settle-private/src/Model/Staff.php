<?php
declare(strict_types=1);
namespace Settle\Model;

use Settle\Database;

final class Staff
{
    public static function blank(): array
    {
        return [
            'id'             => null,
            'full_name'      => '',
            'title'          => '',
            'email'          => '',
            'phone'          => '',
            'bio_html'       => '',
            'photo_media_id' => null,
            'sort_order'     => 0,
            'is_visible'     => 1,
        ];
    }

    /**
     * Fetch all staff (visible + hidden) with photo info joined.
     * Used by the admin index. Public-side rendering filters by is_visible.
     */
    public static function all(): array
    {
        return Database::query(
            'SELECT s.*,
                    m.filename       AS photo_filename,
                    m.alt_text       AS photo_alt,
                    m.original_name  AS photo_original_name
               FROM staff s
               LEFT JOIN media m ON m.id = s.photo_media_id
              ORDER BY s.sort_order, s.id'
        )->fetchAll();
    }

    /**
     * Public listing — visible only, with photo data joined.
     */
    public static function allVisible(): array
    {
        return Database::query(
            'SELECT s.*,
                    m.filename  AS photo_filename,
                    m.alt_text  AS photo_alt
               FROM staff s
               LEFT JOIN media m ON m.id = s.photo_media_id
              WHERE s.is_visible = 1
              ORDER BY s.sort_order, s.id'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $row = Database::query(
            'SELECT s.*,
                    m.filename  AS photo_filename,
                    m.alt_text  AS photo_alt
               FROM staff s
               LEFT JOIN media m ON m.id = s.photo_media_id
              WHERE s.id = :id',
            [':id' => $id]
        )->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        // New entries go to the end of the order.
        $maxOrder = (int)Database::query(
            'SELECT COALESCE(MAX(sort_order), 0) FROM staff'
        )->fetchColumn();

        Database::query(
            'INSERT INTO staff
                (full_name, title, email, phone, bio_html, photo_media_id, sort_order, is_visible)
             VALUES
                (:full_name, :title, :email, :phone, :bio, :photo, :sort, :visible)',
            [
                ':full_name' => $data['full_name'],
                ':title'     => $data['title'] === '' ? null : $data['title'],
                ':email'     => $data['email'] === '' ? null : $data['email'],
                ':phone'     => $data['phone'] === '' ? null : $data['phone'],
                ':bio'       => $data['bio_html'] === '' ? null : $data['bio_html'],
                ':photo'     => !empty($data['photo_media_id']) ? (int)$data['photo_media_id'] : null,
                ':sort'      => $maxOrder + 10,
                ':visible'   => (int)$data['is_visible'],
            ]
        );
        return (int)Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::query(
            'UPDATE staff
                SET full_name      = :full_name,
                    title          = :title,
                    email          = :email,
                    phone          = :phone,
                    bio_html       = :bio,
                    photo_media_id = :photo,
                    is_visible     = :visible
              WHERE id = :id',
            [
                ':full_name' => $data['full_name'],
                ':title'     => $data['title'] === '' ? null : $data['title'],
                ':email'     => $data['email'] === '' ? null : $data['email'],
                ':phone'     => $data['phone'] === '' ? null : $data['phone'],
                ':bio'       => $data['bio_html'] === '' ? null : $data['bio_html'],
                ':photo'     => !empty($data['photo_media_id']) ? (int)$data['photo_media_id'] : null,
                ':visible'   => (int)$data['is_visible'],
                ':id'        => $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM staff WHERE id = :id', [':id' => $id]);
    }

    public static function toggleVisible(int $id): void
    {
        Database::query(
            'UPDATE staff SET is_visible = 1 - is_visible WHERE id = :id',
            [':id' => $id]
        );
    }

    /**
     * Apply a new order. $idsInOrder is an array of staff IDs in desired
     * display sequence. Uses sort_order increments of 10 so future inserts
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
                'UPDATE staff SET sort_order = :sort WHERE id = :id'
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
