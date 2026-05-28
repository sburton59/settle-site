<?php
declare(strict_types=1);
namespace Settle\Model;

use Settle\Database;

final class Page
{
    public static function blank(): array
    {
        return [
            'id'               => null,
            'title'            => '',
            'slug'             => '',
            'body_html'        => '',
            'meta_description' => '',
            'show_in_nav'      => 1,
            'is_published'     => 1,
        ];
    }

    public static function all(): array
    {
        return Database::query(
            'SELECT p.*, u.display_name AS updated_by_name
             FROM pages p
             LEFT JOIN users u ON u.id = p.updated_by
             ORDER BY p.menu_order, p.title'
        )->fetchAll();
    }

    /**
     * Public listing — published pages only, ordered by menu_order then title.
     * Used by the menu URL picker to populate the "Pages" group of link
     * destinations. Mirrors Staff::allVisible() in role.
     */
    public static function allPublished(): array
    {
        return Database::query(
            'SELECT id, slug, title, menu_order
             FROM pages
             WHERE is_published = 1
             ORDER BY menu_order, title'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $row = Database::query('SELECT * FROM pages WHERE id = :id', [':id' => $id])->fetch();
        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $row = Database::query(
            'SELECT * FROM pages WHERE slug = :s AND is_published = 1 LIMIT 1',
            [':s' => $slug]
        )->fetch();
        return $row ?: null;
    }

    public static function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT 1 FROM pages WHERE slug = :s' . ($ignoreId ? ' AND id <> :id' : '');
        $params = [':s' => $slug];
        if ($ignoreId) $params[':id'] = $ignoreId;
        return (bool)Database::query($sql, $params)->fetchColumn();
    }

    public static function create(array $data, int $userId): int
    {
        Database::query(
            'INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
             VALUES (:slug, :title, :body, :meta, :nav, :pub, :uid)',
            [
                ':slug'  => $data['slug'],
                ':title' => $data['title'],
                ':body'  => $data['body_html'],
                ':meta'  => $data['meta_description'],
                ':nav'   => $data['show_in_nav'],
                ':pub'   => $data['is_published'],
                ':uid'   => $userId,
            ]
        );
        return (int)Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data, int $userId): void
    {
        Database::query(
            'UPDATE pages SET
                slug             = :slug,
                title            = :title,
                body_html        = :body,
                meta_description = :meta,
                show_in_nav      = :nav,
                is_published     = :pub,
                updated_by       = :uid
             WHERE id = :id',
            [
                ':slug'  => $data['slug'],
                ':title' => $data['title'],
                ':body'  => $data['body_html'],
                ':meta'  => $data['meta_description'],
                ':nav'   => $data['show_in_nav'],
                ':pub'   => $data['is_published'],
                ':uid'   => $userId,
                ':id'    => $id,
            ]
        );
    }

    public static function togglePublished(int $id, int $userId): void
    {
        Database::query(
            'UPDATE pages
             SET is_published = 1 - is_published, updated_by = :uid
             WHERE id = :id',
            [':uid' => $userId, ':id' => $id]
        );
    }
}
