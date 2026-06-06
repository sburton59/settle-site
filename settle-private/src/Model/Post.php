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
     * Public single post: published AND its publish time has arrived. The
     * "now" comparison is bound from PHP (app timezone, America/Chicago per
     * bootstrap.php) rather than SQL NOW(), so a future-dated post is hidden
     * exactly until its scheduled moment regardless of the database's own
     * timezone. Includes author display name + featured image.
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
               AND p.published_at <= :now
             LIMIT 1',
            [':s' => $slug, ':pub' => 'published', ':now' => self::now()]
        )->fetch();
        return $row ?: null;
    }

    /**
     * Fetch a post by slug regardless of status or publish time. Used only
     * for the signed-in staff PREVIEW path (a scheduled, draft, or archived
     * post that is not yet publicly visible). The controller is responsible
     * for the permission check before showing the result.
     */
    public static function findBySlugAny(string $slug): ?array
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
             LIMIT 1',
            [':s' => $slug]
        )->fetch();
        return $row ?: null;
    }

    /**
     * Public listing — published posts whose publish time has arrived, newest
     * first. Scheduled (future-dated) posts are excluded via the bound :now
     * comparison; ordering falls back to created_at when published_at is
     * somehow missing. $limit/$offset are cast to int and inlined: with PDO
     * emulation off, LIMIT/OFFSET cannot be bound (PROJECT_HANDOFF.md §9).
     */
    public static function publishedList(int $limit, int $offset = 0): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);
        return Database::query(
            'SELECT p.*,
                    u.display_name      AS author_name,
                    m.filename          AS featured_filename,
                    m.thumbnail_filename AS featured_thumbnail,
                    m.alt_text          AS featured_alt
             FROM posts p
             LEFT JOIN users u ON u.id = p.author_id
             LEFT JOIN media m ON m.id = p.featured_media_id
             WHERE p.status = :pub
               AND p.published_at IS NOT NULL
               AND p.published_at <= :now
             ORDER BY COALESCE(p.published_at, p.created_at) DESC
             LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
            [':pub' => 'published', ':now' => self::now()]
        )->fetchAll();
    }

    public static function publishedCount(): int
    {
        return (int) Database::query(
            'SELECT COUNT(*) FROM posts
             WHERE status = :pub AND published_at IS NOT NULL AND published_at <= :now',
            [':pub' => 'published', ':now' => self::now()]
        )->fetchColumn();
    }

    /**
     * Compact summary for the admin dashboard (roadmap #8b): post counts by
     * effective state plus a short recent list. Editors see all posts;
     * authors see only their own (mirrors allForAdmin()'s scoping). State
     * follows the blog's scheduling model — "Scheduled" = published status
     * with a future (or not-yet-stamped) published_at; "Published" =
     * published_at <= now. Time is PHP-bound (:now / self::now(), §13.8).
     *
     * @return array{counts: array{published:int,scheduled:int,draft:int}, recent: array<int,array<string,mixed>>}
     */
    public static function dashboardSummary(int $viewerId, bool $isEditor, int $recentLimit = 5): array
    {
        $now    = self::now();
        $where  = $isEditor ? '' : ' WHERE author_id = :vid';
        $params = $isEditor ? [] : [':vid' => $viewerId];

        $row = Database::query(
            'SELECT
                SUM(CASE WHEN status = :pub  AND published_at IS NOT NULL AND published_at <= :now  THEN 1 ELSE 0 END) AS published,
                SUM(CASE WHEN status = :pub2 AND (published_at IS NULL OR published_at > :now2)      THEN 1 ELSE 0 END) AS scheduled,
                SUM(CASE WHEN status = :draft THEN 1 ELSE 0 END) AS draft
             FROM posts' . $where,
            $params + [':pub' => 'published', ':now' => $now, ':pub2' => 'published', ':now2' => $now, ':draft' => 'draft']
        )->fetch();

        $counts = [
            'published' => (int) ($row['published'] ?? 0),
            'scheduled' => (int) ($row['scheduled'] ?? 0),
            'draft'     => (int) ($row['draft'] ?? 0),
        ];

        $recentLimit = max(1, min(20, $recentLimit));
        $rows = Database::query(
            'SELECT p.id, p.title, p.status, p.published_at, p.created_at,
                    u.display_name AS author_name
             FROM posts p
             LEFT JOIN users u ON u.id = p.author_id'
             . $where .
            ' ORDER BY p.created_at DESC
              LIMIT ' . (int) $recentLimit,
            $params
        )->fetchAll();

        $recent = [];
        foreach ($rows as $r) {
            $status = (string) $r['status'];
            $pubAt  = $r['published_at'] ?? null;
            if ($status === 'published') {
                $state = ($pubAt !== null && $pubAt <= $now) ? 'Published' : 'Scheduled';
                $when  = $pubAt ?? $r['created_at'];
            } elseif ($status === 'archived') {
                $state = 'Archived';
                $when  = $r['created_at'];
            } else {
                $state = 'Draft';
                $when  = $r['created_at'];
            }
            $recent[] = [
                'id'          => (int) $r['id'],
                'title'       => (string) $r['title'],
                'state'       => $state,
                'when'        => (string) $when,
                'author_name' => (string) ($r['author_name'] ?? ''),
            ];
        }

        return ['counts' => $counts, 'recent' => $recent];
    }

    /** Public listing filtered to one category (live, published posts only). */
    public static function publishedListByCategory(int $categoryId, int $limit, int $offset = 0): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);
        return Database::query(
            'SELECT p.*,
                    u.display_name      AS author_name,
                    m.filename          AS featured_filename,
                    m.thumbnail_filename AS featured_thumbnail,
                    m.alt_text          AS featured_alt
             FROM posts p
             JOIN post_categories pc ON pc.post_id = p.id AND pc.category_id = :cid
             LEFT JOIN users u ON u.id = p.author_id
             LEFT JOIN media m ON m.id = p.featured_media_id
             WHERE p.status = :pub
               AND p.published_at IS NOT NULL
               AND p.published_at <= :now
             ORDER BY COALESCE(p.published_at, p.created_at) DESC
             LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
            [':cid' => $categoryId, ':pub' => 'published', ':now' => self::now()]
        )->fetchAll();
    }

    public static function publishedCountByCategory(int $categoryId): int
    {
        return (int) Database::query(
            'SELECT COUNT(*) FROM posts p
             JOIN post_categories pc ON pc.post_id = p.id AND pc.category_id = :cid
             WHERE p.status = :pub AND p.published_at IS NOT NULL AND p.published_at <= :now',
            [':cid' => $categoryId, ':pub' => 'published', ':now' => self::now()]
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
     * Create a post. The controller resolves $data['published_at'] (a
     * 'Y-m-d H:i:s' string, or null) according to the publish-date rules,
     * so the model just stores it. A future value = a scheduled post.
     *
     * @return int the new post id
     */
    public static function create(array $data, int $authorId): int
    {
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
                ':published_at' => $data['published_at'] ?? null,
            ]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Update a post's content, status, and publish time. author_id is never
     * touched (ownership doesn't transfer on edit). $data['published_at'] is
     * the controller-resolved value (string or null) — a future value
     * reschedules the post; a past/now value makes it live.
     */
    public static function update(int $id, array $data): void
    {
        Database::query(
            'UPDATE posts SET
                slug              = :slug,
                title             = :title,
                excerpt           = :excerpt,
                body_html         = :body,
                featured_media_id = :media,
                status            = :status,
                published_at      = :published_at
             WHERE id = :id',
            [
                ':slug'         => $data['slug'],
                ':title'        => $data['title'],
                ':excerpt'      => $data['excerpt'] !== '' ? $data['excerpt'] : null,
                ':body'         => $data['body_html'],
                ':media'        => $data['featured_media_id'] ?: null,
                ':status'       => $data['status'],
                ':published_at' => $data['published_at'] ?? null,
                ':id'           => $id,
            ]
        );
    }

    /**
     * Change status (used by the list's quick publish/unpublish/archive). If
     * $publishedAt is non-null it is also written (the quick "Publish" button
     * passes "now"); if null, only the status changes and any existing
     * published_at is left intact.
     */
    public static function setStatus(int $id, string $status, ?string $publishedAt): void
    {
        if ($publishedAt !== null) {
            Database::query(
                'UPDATE posts SET status = :status, published_at = :pa WHERE id = :id',
                [':status' => $status, ':pa' => $publishedAt, ':id' => $id]
            );
        } else {
            Database::query(
                'UPDATE posts SET status = :status WHERE id = :id',
                [':status' => $status, ':id' => $id]
            );
        }
    }

    /** Current time in the app timezone (America/Chicago, set in bootstrap). */
    public static function now(): string
    {
        return date('Y-m-d H:i:s');
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
