<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\AuditLog;
use Settle\Model\Category;

/**
 * Blog category management (roadmap #3).
 *
 * Editor+ only (gated at the route). Categories are the curated list of
 * ministry areas (Music, Youth, Children's Programs, ...) that authors
 * assign to their posts. Deleting a category does not delete any posts;
 * the post_categories rows cascade away and the affected posts simply
 * lose that one tag.
 */
final class CategoryController extends BaseController
{
    public function index(): void
    {
        $this->render('admin/categories/index', [
            'categories' => Category::all(),
        ]);
    }

    public function create(): void
    {
        $this->render('admin/categories/edit', [
            'category' => Category::blank(),
            'isNew'    => true,
            'errors'   => [],
        ]);
    }

    public function store(): void
    {
        $data = $this->collectFormData();
        $errors = $this->validate($data, null);
        if ($errors) {
            $this->render('admin/categories/edit', [
                'category' => array_merge(Category::blank(), $data),
                'isNew'    => true,
                'errors'   => $errors,
            ]);
            return;
        }

        $id = Category::create($data);
        AuditLog::record('category.create', 'category', $id, ['name' => $data['name']]);
        $this->flash('success', 'Category created.');
        $this->redirect('/admin/categories');
    }

    public function edit(array $params): void
    {
        $category = Category::find((int) $params['id']);
        if (!$category) {
            http_response_code(404);
            echo 'Category not found.';
            return;
        }
        $this->render('admin/categories/edit', [
            'category' => $category,
            'isNew'    => false,
            'errors'   => [],
        ]);
    }

    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $category = Category::find($id);
        if (!$category) {
            http_response_code(404);
            echo 'Category not found.';
            return;
        }

        $data = $this->collectFormData();
        $errors = $this->validate($data, $id);
        if ($errors) {
            $this->render('admin/categories/edit', [
                'category' => array_merge($category, $data),
                'isNew'    => false,
                'errors'   => $errors,
            ]);
            return;
        }

        Category::update($id, $data);
        AuditLog::record('category.update', 'category', $id, ['name' => $data['name']]);
        $this->flash('success', 'Category saved.');
        $this->redirect('/admin/categories');
    }

    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $category = Category::find($id);
        if (!$category) {
            http_response_code(404);
            echo 'Category not found.';
            return;
        }

        Category::delete($id);
        AuditLog::record('category.delete', 'category', $id, ['name' => $category['name']]);
        $this->flash('success', 'Category deleted.');
        $this->redirect('/admin/categories');
    }

    // -------------------------------------------------------------------

    private function collectFormData(): array
    {
        $name = trim((string) $this->input('name', ''));
        $slug = trim((string) $this->input('slug', ''));
        if ($slug === '' && $name !== '') {
            $slug = Category::slugify($name);
        }
        return [
            'name'       => $name,
            'slug'       => $slug,
            'sort_order' => (int) $this->input('sort_order', 0),
        ];
    }

    private function validate(array $data, ?int $ignoreId): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors['name'] = 'Name is required.';
        }

        if ($data['slug'] === '') {
            $errors['slug'] = 'Web address is required (it fills in automatically from the name).';
        } elseif (!preg_match('/^[a-z0-9\-]+$/', $data['slug'])) {
            $errors['slug'] = 'Web address may only contain lowercase letters, numbers, and hyphens.';
        } elseif (Category::slugExists($data['slug'], $ignoreId)) {
            $errors['slug'] = 'That web address is already used by another category.';
        }

        return $errors;
    }
}
