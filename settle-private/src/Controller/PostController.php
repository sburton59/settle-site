<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\Auth;
use Settle\AuditLog;
use Settle\Model\Category;
use Settle\Model\Post;

/**
 * Admin blog-post management (roadmap #3).
 *
 * Route role middleware gates these to 'author' or higher. Per-post
 * OWNERSHIP is enforced here, in code, because the router can only express
 * "author or higher", not "the author who owns this post OR any editor":
 *   - authors may edit / publish / delete only their own posts
 *   - editors and admins may manage any post
 * See requireOwnedPost().
 *
 * Categories are a curated list (editor-managed in CategoryController);
 * authors only assign from the existing set via the editor's checkboxes.
 */
final class PostController extends BaseController
{
    private const STATUSES = ['draft', 'published', 'archived'];

    public function index(): void
    {
        $isEditor = Auth::hasRole('editor');
        $posts = Post::allForAdmin((int) $_SESSION['user_id'], $isEditor);
        $this->render('admin/posts/index', [
            'posts'    => $posts,
            'isEditor' => $isEditor,
        ]);
    }

    public function create(): void
    {
        $this->render('admin/posts/edit', [
            'post'         => Post::blank(),
            'isNew'        => true,
            'errors'       => [],
            'categories'   => Category::allForPicker(),
            'selectedCats' => [],
            'featured'     => null,
        ]);
    }

    public function store(): void
    {
        $data = $this->collectFormData();
        if ($data['slug'] === '' && $data['title'] !== '') {
            $data['slug'] = $this->uniqueSlug(Post::slugify($data['title']), null);
        }

        $errors = $this->validate($data, null);
        if ($errors) {
            $this->renderEditWith($data, true, $errors, null);
            return;
        }

        $data['published_at'] = $this->resolvePublishedAt($data['status'], $data['publish_at'], null);

        $id = Post::create($data, (int) $_SESSION['user_id']);
        Post::syncCategories($id, $data['category_ids']);
        AuditLog::record('post.create', 'post', $id, ['title' => $data['title'], 'status' => $data['status']]);
        if ($data['status'] === 'published') {
            $scheduled = $data['published_at'] !== null && $data['published_at'] > Post::now();
            AuditLog::record(
                $scheduled ? 'post.schedule' : 'post.publish',
                'post',
                $id,
                $scheduled ? ['publish_at' => $data['published_at']] : []
            );
        }

        $this->flash('success', $this->savedFlash($data));
        $this->redirect($this->editUrl($id, $data['slug']));
    }

    public function edit(array $params): void
    {
        $post = $this->requireOwnedPost((int) $params['id']);
        if ($post === null) {
            return; // requireOwnedPost already emitted 404/403
        }
        $this->render('admin/posts/edit', [
            'post'         => $post,
            'isNew'        => false,
            'errors'       => [],
            'categories'   => Category::allForPicker(),
            'selectedCats' => Post::categoryIdsFor((int) $post['id']),
            'featured'     => $this->featuredPreview($post),
        ]);
    }

    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $post = $this->requireOwnedPost($id);
        if ($post === null) {
            return;
        }

        $data = $this->collectFormData();
        if ($data['slug'] === '' && $data['title'] !== '') {
            $data['slug'] = $this->uniqueSlug(Post::slugify($data['title']), $id);
        }

        $errors = $this->validate($data, $id);
        if ($errors) {
            // Preserve the post id/author for the re-render.
            $merged = array_merge($post, $data);
            $this->render('admin/posts/edit', [
                'post'         => $merged,
                'isNew'        => false,
                'errors'       => $errors,
                'categories'   => Category::allForPicker(),
                'selectedCats' => $data['category_ids'],
                'featured'     => $this->featuredPreview($merged),
            ]);
            return;
        }

        $data['published_at'] = $this->resolvePublishedAt(
            $data['status'],
            $data['publish_at'],
            $post['published_at'] ?? null
        );
        $becamePublished = ($data['status'] === 'published' && ($post['status'] ?? '') !== 'published');

        Post::update($id, $data);
        Post::syncCategories($id, $data['category_ids']);
        AuditLog::record('post.update', 'post', $id, ['title' => $data['title'], 'status' => $data['status']]);
        if ($becamePublished) {
            $scheduled = $data['published_at'] !== null && $data['published_at'] > Post::now();
            AuditLog::record(
                $scheduled ? 'post.schedule' : 'post.publish',
                'post',
                $id,
                $scheduled ? ['publish_at' => $data['published_at']] : []
            );
        }

        $this->flash('success', $this->savedFlash($data));
        $this->redirect($this->editUrl($id, $data['slug']));
    }

    /**
     * Publish / unpublish (back to draft) / archive via a single endpoint.
     * The desired status arrives in POST 'status'.
     */
    public function setStatus(array $params): void
    {
        $id = (int) $params['id'];
        $post = $this->requireOwnedPost($id);
        if ($post === null) {
            return;
        }

        $status = (string) $this->input('status', '');
        if (!in_array($status, self::STATUSES, true)) {
            $this->flash('error', 'Unknown status.');
            $this->redirect('/admin/posts');
            return;
        }

        // The list's quick "Publish" means "make it live now"; unpublish and
        // archive leave the existing publish date alone (pass null).
        $publishedAt = ($status === 'published') ? Post::now() : null;
        Post::setStatus($id, $status, $publishedAt);

        $verb = match ($status) {
            'published' => 'post.publish',
            'archived'  => 'post.archive',
            default     => 'post.unpublish',
        };
        AuditLog::record($verb, 'post', $id, ['status' => $status]);

        $this->flash('success', 'Post status updated.');
        $this->redirect('/admin/posts');
    }

    /** Flash wording that reflects whether a save scheduled the post. */
    private function savedFlash(array $data): string
    {
        if ($data['status'] === 'published'
            && ($data['published_at'] ?? null) !== null
            && $data['published_at'] > Post::now()) {
            return 'Post scheduled for ' . date('M j, Y \a\t g:i a', strtotime((string) $data['published_at'])) . '.';
        }
        return 'Post saved.';
    }

    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $post = $this->requireOwnedPost($id);
        if ($post === null) {
            return;
        }

        Post::delete($id);
        AuditLog::record('post.delete', 'post', $id, ['title' => $post['title'] ?? '']);

        $this->flash('success', 'Post deleted.');
        $this->redirect('/admin/posts');
    }

    // -------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------

    /**
     * Load a post and enforce ownership. Returns the post row, or null after
     * having already sent a 404 (missing) or 403 (not owner and not editor).
     */
    private function requireOwnedPost(int $id): ?array
    {
        $post = Post::find($id);
        if (!$post) {
            http_response_code(404);
            echo 'Post not found.';
            return null;
        }
        $isOwner = ((int) $post['author_id'] === (int) $_SESSION['user_id']);
        if (!$isOwner && !Auth::hasRole('editor')) {
            http_response_code(403);
            echo 'Forbidden — you can only manage your own posts.';
            return null;
        }
        return $post;
    }

    private function renderEditWith(array $data, bool $isNew, array $errors, ?int $id): void
    {
        $post = array_merge(Post::blank(), $data);
        if ($id !== null) {
            $post['id'] = $id;
        }
        $this->render('admin/posts/edit', [
            'post'         => $post,
            'isNew'        => $isNew,
            'errors'       => $errors,
            'categories'   => Category::allForPicker(),
            'selectedCats' => $data['category_ids'],
            'featured'     => $this->featuredPreview($post),
        ]);
    }

    private function collectFormData(): array
    {
        $cats = $this->input('category_ids', []);
        if (!is_array($cats)) {
            $cats = [];
        }
        $cats = array_map('intval', $cats);

        $media = (int) $this->input('featured_media_id', 0);

        return [
            'title'             => trim((string) $this->input('title', '')),
            'slug'              => trim((string) $this->input('slug', '')),
            'excerpt'           => trim((string) $this->input('excerpt', '')),
            'body_html'         => (string) $this->input('body_html', ''),
            'featured_media_id' => $media > 0 ? $media : null,
            'status'            => (string) $this->input('status', 'draft'),
            'publish_at'        => trim((string) $this->input('publish_at', '')),
            'category_ids'      => $cats,
        ];
    }

    private function validate(array $data, ?int $ignoreId): array
    {
        $errors = [];

        if ($data['title'] === '') {
            $errors['title'] = 'Title is required.';
        }

        if ($data['slug'] === '') {
            $errors['slug'] = 'Web address is required (it is filled in automatically from the title).';
        } elseif (!preg_match('/^[a-z0-9\-]+$/', $data['slug'])) {
            $errors['slug'] = 'Web address may only contain lowercase letters, numbers, and hyphens.';
        } elseif (Post::slugExists($data['slug'], $ignoreId)) {
            $errors['slug'] = 'That web address is already used by another post.';
        }

        if (!in_array($data['status'], self::STATUSES, true)) {
            $errors['status'] = 'Please choose a valid status.';
        }

        if ($data['status'] === 'published'
            && $data['publish_at'] !== ''
            && strtotime($data['publish_at']) === false) {
            $errors['publish_at'] = 'Enter a valid date and time, or leave it blank to publish now.';
        }

        if (mb_strlen($data['excerpt']) > 500) {
            $errors['excerpt'] = 'Summary must be 500 characters or fewer.';
        }

        return $errors;
    }

    /**
     * Resolve the published_at value to store, given the chosen status, the
     * raw datetime-local input, and the post's existing published_at.
     *   - not published        → leave the existing value untouched
     *   - a parseable date/time → use it (future = scheduled; past/now = live)
     *   - blank                 → keep the existing date, or "now" if none yet
     * The "now" baseline is the app timezone (America/Chicago, per bootstrap),
     * matching the bound :now the public queries compare against.
     */
    private function resolvePublishedAt(string $status, string $raw, ?string $existing): ?string
    {
        if ($status !== 'published') {
            return $existing;
        }
        $raw = trim($raw);
        if ($raw !== '') {
            $ts = strtotime($raw);
            if ($ts !== false) {
                return date('Y-m-d H:i:s', $ts);
            }
        }
        return $existing ?: Post::now();
    }

    /**
     * Ensure a slug is unique by appending -2, -3, ... when needed. Used only
     * for slugs auto-generated from the title; a hand-typed duplicate slug is
     * reported as a validation error instead.
     */
    private function uniqueSlug(string $base, ?int $ignoreId): string
    {
        if ($base === '') {
            return '';
        }
        $slug = $base;
        $n = 1;
        while (Post::slugExists($slug, $ignoreId)) {
            $n++;
            $slug = $base . '-' . $n;
        }
        return $slug;
    }

    /** Build a small {url, alt} preview for the featured image, if any. */
    private function featuredPreview(array $post): ?array
    {
        $fn = $post['featured_filename'] ?? null;
        if ($fn) {
            return ['url' => '/uploads/' . $fn, 'alt' => $post['featured_alt'] ?? ''];
        }
        // On a validation re-render we only have the id; let the template
        // fall back to "no preview" rather than doing an extra query here.
        return null;
    }

    private function editUrl(int $id, string $slug): string
    {
        $url = "/admin/posts/$id/edit";
        if (!empty($_POST['preview'])) {
            $url .= '?preview=1';
        }
        return $url;
    }
}
