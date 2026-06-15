<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\Help;

/**
 * Admin help doc (roadmap #14).
 *
 * One source of truth (\Settle\Help), rendered two ways:
 *   GET /admin/help          full single-page doc, anchored per section
 *   GET /admin/help/{slug}   one section on its own (for printing a single
 *                            section); same content, no duplication
 *
 * Auth required, but NO role gate: every signed-in user may read the help.
 * The doc itself documents who can DO what (the capability matrix), so it
 * has to be readable by all three roles. Data is passed under safe keys
 * (never data/template/layout/content/e — they collide with View::render
 * under EXTR_SKIP; see PROJECT_HANDOFF.md §13.9).
 */
final class HelpController extends BaseController
{
    /** GET /admin/help — the complete doc. */
    public function index(): void
    {
        $this->render('admin/help/index', [
            'sections'   => Help::sections(),
            'roleLabels' => Help::roleLabels(),
        ]);
    }

    /** GET /admin/help/{slug} — a single section, printable on its own. */
    public function section(array $params): void
    {
        $slug    = (string)($params['slug'] ?? '');
        $section = Help::findSection($slug);
        if ($section === null) {
            http_response_code(404);
            echo 'Help section not found.';
            return;
        }
        $this->render('admin/help/section', [
            'section'     => $section,
            'allSections' => Help::sections(),
            'roleLabels'  => Help::roleLabels(),
        ]);
    }
}
