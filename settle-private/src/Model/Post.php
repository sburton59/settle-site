<?php
declare(strict_types=1);
namespace Settle\Model;

use Settle\Database;

/**
 * Multi-author blog posts (roadmap #3).
 *
 * Ownership: a post belongs to one author (author_id, RESTRICT). Authors
 * may edit/publish/delete only their own posts; editors+ may manage any.
 * That rule is enforced in PostController (in-code), because the router's
 * role middleware can only express "author or higher", not "owner OR editor".
 *
 * published_at is stamped the first time a post transitions to 'published'
 * and is never overwritten by later edits (so the public ordering by
 * publish date is stable). See setStatus()/create()/update().
 *
 * Categories are many-to-many via the post_categories junction. body_html
 * is the one trusted column (admin-authored, rendered unescaped); inline
 * images live inside it exactly as they do for Pages — there is no separate
 * inline-image tracking table in use.
 */
final class Post
{
    public static function blank(): array
    {
        return [
            'id'                => null,
            'title'             => '',
            'slug'              => '',
            'excerpt'           => '',
            'body_html'         => '',
            'featured_media_id' => null,
            'status'            => 'draft',
            'published_at'      => null,
        ];
    }

    /**
     * Admin index rows. Editors see every post; authors see only their own.
     * Includes author display name, featured-image filename, and a
     * comma-separated list of category names for the list column.
     *
     * @param int  $viewerId  the logged-in user's id
     * @param bool $isEditor  true if the viewer is editor or admin
     */
    public static function allForAdmin(int $viewerId, bool $isEditor): array
    {
        $sql =
            'SELECT p.*,
                    u.display_name AS author_name,
                    m.filename     AS featured_filename,
                    GROUP_CONCAT(c.name ORDER BY c.sort_order, c.name SEPARATOR ", ") AS category_names
             FROM posts p
             LEFT JOIN users u            ON u.id = p.author_id
             LEFT JOIN media m            ON m.id = p.featured_media_id
             LEFT JOIN post_categories pc ON pc.post_id = p.id
             LEFT JOIN categories c       ON c.id = pc.category_id';
        $params = [];
        if (!$isEditor) {
            $sql .= ' WHERE p.author_id = :vid';
            $params[':vid'] = $viewerId;
        }
        $sql .= ' GROUP BY p.id ORDER BY p.created_at DESC';

        return Database::query($sql, $params)->fetchAll();
    }

    /**
     * Single post for the admin editor, with the featured image's filename
     * and alt text joined in so the editor can render a preview. All posts
     * columns are returned via p.*, plus featured_filename / featured_alt.
     */
    public static function find(int $id): ?array
    {
        $row = Database::query(
            'SELECT p.*,
                    m.filename AS featured_filename,
                    m.alt_text AS featured_alt
             FROM posts p
             LEFT JOIN media m ON m.id = p.featured_media_id
             WHERE p.id = :id',
            [':id' => $id]
        )->fetch();
        return $row ?: null;
    }

    /**
     * Public single post: published only, and only once its publish time
     * has arrived. Includes author display name + featured image.
     */
    public static function findBySlugPublished(string $slug): ?array
    {
        $row = Database::query(
            'SELECT p.*,
                    u.display_name AS author_name,
                    m.filename     AS featured_filename,
                    m.alt_text     AS featured_alt
             FROM posts p
             LEFT JOIN users u ON u.id = p.author_id
             LEFT JOIN media m ON m.id = p.featured_media_id
             WHERE p.slug = :s
               AND p.status = :pub
               AND p.published_at IS NOT NULL
               AND p.published_at <= NOW()
             LIMIT 1',
            [':s' => $slug, ':pub' => 'published']
        )->fetch();
        return $row ?: null;
    }

    /**
     * Public listing — published posts, newest first. $limit/$offset are
     * cast to int and inlined: with PDO emulation off, LIMIT/OFFSET cannot
     * be bound as parameters (see PROJECT_HANDOFF.md §9, CalendarEvent::upcoming).
     */
    public static function publishedList(int $limit, int $offset = 0): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);
        return Database::query(
            'SELECT p.*,
                    u.display_name AS author_name,
                    m.filename     AS featured_filename,
                    m.alt_text     AS featured_alt
             FROM posts p
             LEFT JOIN users u ON u.id = p.author_id
             LEFT JOIN media m ON m.id = p.featured_media_id
             WHERE p.status = :pub
               AND p.published_at IS NOT NULL
               AND p.published_at <= NOW()
             ORDER BY p.published_at DESC
             LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
            [':pub' => 'published']
        )->fetchAll();
    }

    public static function publishedCount(): int
    {
        return (int) Database::query(
            'SELECT COUNT(*) FROM posts
             WHERE status = :pub AND published_at IS NOT NULL AND published_at <= NOW()',
            [':pub' => 'published']
        )->fetchColumn();
    }

    /** Public listing filtered to one category. */
    public static function publishedListByCategory(int $categoryId, int $limit, int $offset = 0): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);
        return Database::query(
            'SELECT p.*,
                    u.display_name AS author_name,
                    m.filename     AS featured_filename,
                    m.alt_text     AS featured_alt
             FROM posts p
             JOIN post_categories pc ON pc.post_id = p.id AND pc.category_id = :cid
             LEFT JOIN users u ON u.id = p.author_id
             LEFT JOIN media m ON m.id = p.featured_media_id
             WHERE p.status = :pub
               AND p.published_at IS NOT NULL
               AND p.published_at <= NOW()
             ORDER BY p.published_at DESC
             LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
            [':cid' => $categoryId, ':pub' => 'published']
        )->fetchAll();
    }

    public static function publishedCountByCategory(int $categoryId): int
    {
        return (int) Database::query(
            'SELECT COUNT(*) FROM posts p
             JOIN post_categories pc ON pc.post_id = p.id AND pc.category_id = :cid
             WHERE p.status = :pub AND p.published_at IS NOT NULL AND p.published_at <= NOW()',
            [':cid' => $categoryId, ':pub' => 'published']
        )->fetchColumn();
    }

    public static function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT 1 FROM posts WHERE slug = :s' . ($ignoreId ? ' AND id <> :id' : '');
        $params = [':s' => $slug];
        if ($ignoreId) {
            $params[':id'] = $ignoreId;
        }
        return (bool) Database::query($sql, $params)->fetchColumn();
    }

    /**
     * Create a post. published_at is stamped only if the post is created
     * directly as 'published'.
     *
     * @return int the new post id
     */
    public static function create(array $data, int $authorId): int
    {
        $publishedAt = ($data['status'] === 'published') ? date('Y-m-d H:i:s') : null;

        Database::query(
            'INSERT INTO posts
                (slug, title, excerpt, body_html, featured_media_id, author_id, status, published_at)
             VALUES
                (:slug, :title, :excerpt, :body, :media, :author, :status, :published_at)',
            [
                ':slug'         => $data['slug'],
                ':title'        => $data['title'],
                ':excerpt'      => $data['excerpt'] !== '' ? $data['excerpt'] : null,
                ':body'         => $data['body_html'],
                ':media'        => $data['featured_media_id'] ?: null,
                ':author'       => $authorId,
                ':status'       => $data['status'],
                ':published_at' => $publishedAt,
            ]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Update a post's content + status. author_id is intentionally NOT
     * touched (ownership never transfers on edit). published_at is stamped
     * the first time the post becomes 'published' and is otherwise left
     * exactly as it was.
     */
    public static function update(int $id, array $data, ?string $existingPublishedAt): void
    {
        $stampNow = ($data['status'] === 'published' && empty($existingPublishedAt));

        $sql =
            'UPDATE posts SET
                slug              = :slug,
                title             = :title,
                excerpt           = :excerpt,
                body_html         = :body,
                featured_media_id = :media,
                status            = :status'
            . ($stampNow ? ', published_at = :published_at' : '') .
            ' WHERE id = :id';

        $params = [
            ':slug'    => $data['slug'],
            ':title'   => $data['title'],
            ':excerpt' => $data['excerpt'] !== '' ? $data['excerpt'] : null,
            ':body'    => $data['body_html'],
            ':media'   => $data['featured_media_id'] ?: null,
            ':status'  => $data['status'],
            ':id'      => $id,
        ];
        if ($stampNow) {
            $params[':published_at'] = date('Y-m-d H:i:s');
        }

        Database::query($sql, $params);
    }

    /**
     * Change only the status (publish / unpublish / archive). Stamps
     * published_at the first time the post becomes published; never clears it.
     */
    public static function setStatus(int $id, string $status, ?string $existingPublishedAt): void
    {
        $stampNow = ($status === 'published' && empty($existingPublishedAt));

        if ($stampNow) {
            Database::query(
                'UPDATE posts SET status = :status, published_at = :pa WHERE id = :id',
                [':status' => $status, ':pa' => date('Y-m-d H:i:s'), ':id' => $id]
            );
        } else {
            Database::query(
                'UPDATE posts SET status = :status WHERE id = :id',
                [':status' => $status, ':id' => $id]
            );
        }
    }

    public static function delete(int $id): void
    {
        // post_categories rows are removed by ON DELETE CASCADE.
        Database::query('DELETE FROM posts WHERE id = :id', [':id' => $id]);
    }

    // ---- Categories ---------------------------------------------------

    /** Category ids currently assigned to a post (for the edit form). */
    public static function categoryIdsFor(int $postId): array
    {
        $rows = Database::query(
            'SELECT category_id FROM post_categories WHERE post_id = :pid',
            [':pid' => $postId]
        )->fetchAll();
        return array_map(static fn($r) => (int) $r['category_id'], $rows);
    }

    /** Category name/slug pairs assigned to a post (for public display). */
    public static function categoriesFor(int $postId): array
    {
        return Database::query(
            'SELECT c.id, c.name, c.slug
             FROM post_categories pc
             JOIN categories c ON c.id = pc.category_id
             WHERE pc.post_id = :pid
             ORDER BY c.sort_order, c.name',
            [':pid' => $postId]
        )->fetchAll();
    }

    /**
     * Replace a post's category assignments with the given set, in a
     * transaction (delete-all then insert). Unknown/blank ids are ignored;
     * duplicates collapse via the junction's composite primary key.
     *
     * @param int[] $categoryIds
     */
    public static function syncCategories(int $postId, array $categoryIds): void
    {
        $clean = [];
        foreach ($categoryIds as $cid) {
            $cid = (int) $cid;
            if ($cid > 0) {
                $clean[$cid] = true; // dedupe
            }
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            Database::query('DELETE FROM post_categories WHERE post_id = :pid', [':pid' => $postId]);
            if ($clean !== []) {
                $stmt = $pdo->prepare(
                    'INSERT INTO post_categories (post_id, category_id) VALUES (:pid, :cid)'
                );
                foreach (array_keys($clean) as $cid) {
                    $stmt->execute([':pid' => $postId, ':cid' => $cid]);
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Slugify a title. Lowercase, alphanumeric runs to single hyphens,
     * trimmed. Matches the slug character rules the editor validates against.
     */
    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        return trim($text, '-');
    }
}
