<?php
declare(strict_types=1);
namespace Settle\Model;

use Settle\Database;

/**
 * Blog categories — a curated, editor-managed list of ministry areas
 * (Music, Youth, Children's Programs, etc.). Posts relate to categories
 * many-to-many via the post_categories junction (see Post::syncCategories).
 *
 * Authors assign from this list when writing; only editors+ create,
 * rename, or delete categories (enforced at the route + controller level).
 */
final class Category
{
    public static function blank(): array
    {
        return [
            'id'         => null,
            'name'       => '',
            'slug'       => '',
            'sort_order' => 0,
        ];
    }

    /**
     * All categories, ordered for the admin list and the post-editor
     * checkbox group. Includes a published-post count for the admin index.
     */
    public static function all(): array
    {
        return Database::query(
            'SELECT c.*,
                    (SELECT COUNT(*)
                       FROM post_categories pc
                       JOIN posts p ON p.id = pc.post_id
                      WHERE pc.category_id = c.id
                        AND p.status = :pub) AS post_count
             FROM categories c
             ORDER BY c.sort_order, c.name',
            [':pub' => 'published']
        )->fetchAll();
    }

    /**
     * Lightweight list for the post-editor checkbox group — id, name, slug
     * in display order. No counts.
     */
    public static function allForPicker(): array
    {
        return Database::query(
            'SELECT id, name, slug FROM categories ORDER BY sort_order, name'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $row = Database::query('SELECT * FROM categories WHERE id = :id', [':id' => $id])->fetch();
        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $row = Database::query(
            'SELECT * FROM categories WHERE slug = :s LIMIT 1',
            [':s' => $slug]
        )->fetch();
        return $row ?: null;
    }

    public static function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT 1 FROM categories WHERE slug = :s' . ($ignoreId ? ' AND id <> :id' : '');
        $params = [':s' => $slug];
        if ($ignoreId) {
            $params[':id'] = $ignoreId;
        }
        return (bool) Database::query($sql, $params)->fetchColumn();
    }

    public static function create(array $data): int
    {
        Database::query(
            'INSERT INTO categories (slug, name, sort_order)
             VALUES (:slug, :name, :sort)',
            [
                ':slug' => $data['slug'],
                ':name' => $data['name'],
                ':sort' => (int) $data['sort_order'],
            ]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::query(
            'UPDATE categories
                SET slug = :slug, name = :name, sort_order = :sort
              WHERE id = :id',
            [
                ':slug' => $data['slug'],
                ':name' => $data['name'],
                ':sort' => (int) $data['sort_order'],
                ':id'   => $id,
            ]
        );
    }

    /**
     * Delete a category. The post_categories rows that reference it are
     * removed automatically by the ON DELETE CASCADE foreign key, so a
     * category can be deleted even while posts are assigned to it (the
     * posts simply lose that one tag).
     */
    public static function delete(int $id): void
    {
        Database::query('DELETE FROM categories WHERE id = :id', [':id' => $id]);
    }

    /**
     * Turn a free-text name into a URL-safe slug. Mirrors the slug rules
     * the Pages slug field validates against: lowercase a-z, 0-9, hyphens.
     */
    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        return trim($text, '-');
    }
}
