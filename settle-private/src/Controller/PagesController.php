<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\Model\Page;

final class PagesController extends BaseController
{
    public function index(): void
    {
        $pages = Page::all();
        $this->render('admin/pages/index', ['pages' => $pages]);
    }

    public function create(): void
    {
        $this->render('admin/pages/edit', [
            'page'   => Page::blank(),
            'isNew'  => true,
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $data = $this->collectFormData();
        $errors = $this->validate($data);
        if ($errors) {
            $this->render('admin/pages/edit', [
                'page'   => array_merge(Page::blank(), $data),
                'isNew'  => true,
                'errors' => $errors,
            ]);
            return;
        }
        $id = Page::create($data, (int)$_SESSION['user_id']);
        $this->flash('success', 'Page created.');
        $this->redirect($this->editUrl($id));
    }

    public function edit(array $params): void
    {
        $page = Page::find((int)$params['id']);
        if (!$page) { http_response_code(404); echo 'Page not found.'; return; }
        $this->render('admin/pages/edit', [
            'page'   => $page,
            'isNew'  => false,
            'errors' => [],
        ]);
    }

    public function update(array $params): void
    {
        $id = (int)$params['id'];
        $page = Page::find($id);
        if (!$page) { http_response_code(404); echo 'Page not found.'; return; }

        $data = $this->collectFormData();
        $errors = $this->validate($data, $id);
        if ($errors) {
            $this->render('admin/pages/edit', [
                'page'   => array_merge($page, $data),
                'isNew'  => false,
                'errors' => $errors,
            ]);
            return;
        }
        Page::update($id, $data, (int)$_SESSION['user_id']);
        $this->flash('success', 'Page saved.');
        $this->redirect($this->editUrl($id));
    }

    public function toggleHide(array $params): void
    {
        Page::togglePublished((int)$params['id'], (int)$_SESSION['user_id']);
        $this->flash('success', 'Page visibility updated.');
        $this->redirect('/admin/pages');
    }

    /**
     * Build the post-save redirect URL. If the user clicked "Save & Preview"
     * (which sets a `preview=1` hidden field), append that to the URL so the
     * edit template knows to pop open the public-facing page in a new tab.
     */
    private function editUrl(int $id): string
    {
        $url = "/admin/pages/$id/edit";
        if (!empty($_POST['preview'])) {
            $url .= '?preview=1';
        }
        return $url;
    }

    private function collectFormData(): array
    {
        return [
            'title'            => trim((string)$this->input('title', '')),
            'slug'             => trim((string)$this->input('slug', '')),
            'body_html'        => (string)$this->input('body_html', ''),
            'meta_description' => trim((string)$this->input('meta_description', '')),
            'show_in_nav'      => $this->input('show_in_nav') ? 1 : 0,
            'is_published'     => $this->input('is_published') ? 1 : 0,
        ];
    }

    private function validate(array $data, ?int $ignoreId = null): array
    {
        $errors = [];
        if ($data['title'] === '')
            $errors['title'] = 'Title is required.';
        if ($data['slug'] === '')
            $errors['slug'] = 'Web address is required.';
        elseif (!preg_match('/^[a-z0-9\-\/]+$/', $data['slug']))
            $errors['slug'] = 'Web address may only contain lowercase letters, numbers, hyphens, and slashes.';
        elseif (Page::slugExists($data['slug'], $ignoreId))
            $errors['slug'] = 'That web address is already in use.';
        if (mb_strlen($data['meta_description']) > 300)
            $errors['meta_description'] = 'Search summary must be 300 characters or fewer.';
        return $errors;
    }
}
