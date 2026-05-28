<?php
declare(strict_types=1);

/**
 * Settle Memorial UMC — front controller.
 *
 * This is the ONLY PHP file inside the public web root.
 * Everything else lives in ../../settle-private/, outside public_html,
 * so it cannot be reached by any URL.
 *
 * Routes are gated by \Settle\Features::enabled() at the route-block
 * level. Disabled features return a clean 404 (no route registered).
 * For Settle, every flag is true; the gates are plumbing so the
 * eventual multi-church split is mechanical, not a retrofit.
 * See PROJECT_HANDOFF.md §9 and §14.4.
 */

require __DIR__ . '/../../settle-private/src/bootstrap.php';

use Settle\Features;
use Settle\Router;
use Settle\Controller\AuthController;
use Settle\Controller\ContactMessageController;
use Settle\Controller\DashboardController;
use Settle\Controller\MediaController;
use Settle\Controller\MenuController;
use Settle\Controller\PagesController;
use Settle\Controller\PrayerRequestController;
use Settle\Controller\PublicController;
use Settle\Controller\SlideshowController;
use Settle\Controller\StaffController;

$router = new Router();

// -------------------------------------------------------------------
// Always-on routes (core, not feature-gated)
// -------------------------------------------------------------------

// Public homepage
$router->get('/', [PublicController::class, 'home']);

// Auth
$router->get ('/admin/login',  [AuthController::class, 'showLogin']);
$router->post('/admin/login',  [AuthController::class, 'doLogin']);
$router->post('/admin/logout', [AuthController::class, 'logout']);

// Admin dashboard
$router->get('/admin', [DashboardController::class, 'index'], ['auth' => true]);

// -------------------------------------------------------------------
// Pages
// -------------------------------------------------------------------
if (Features::enabled('pages')) {
    $router->get ('/page/{slug}', [PublicController::class, 'page']);

    $router->get ('/admin/pages',           [PagesController::class, 'index'],      ['auth' => true]);
    $router->get ('/admin/pages/new',       [PagesController::class, 'create'],     ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/pages',           [PagesController::class, 'store'],      ['auth' => true, 'role' => 'editor']);
    $router->get ('/admin/pages/{id}/edit', [PagesController::class, 'edit'],       ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/pages/{id}',      [PagesController::class, 'update'],     ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/pages/{id}/hide', [PagesController::class, 'toggleHide'], ['auth' => true, 'role' => 'editor']);
}

// -------------------------------------------------------------------
// Media Library
// -------------------------------------------------------------------
if (Features::enabled('media')) {
    $router->get ('/admin/media',                    [MediaController::class, 'index'],            ['auth' => true]);
    $router->post('/admin/media',                    [MediaController::class, 'upload'],           ['auth' => true]);
    $router->get ('/admin/media/picker',              [MediaController::class, 'picker'],           ['auth' => true]);
    $router->post('/admin/media/upload-from-editor', [MediaController::class, 'uploadFromEditor'], ['auth' => true]);
    $router->get ('/admin/media/{id}/edit',           [MediaController::class, 'edit'],             ['auth' => true]);
    $router->post('/admin/media/{id}',                [MediaController::class, 'update'],           ['auth' => true]);
    $router->post('/admin/media/{id}/delete',         [MediaController::class, 'destroy'],          ['auth' => true]);
}

// -------------------------------------------------------------------
// Homepage Slideshow (editor+ only — it's homepage chrome)
// -------------------------------------------------------------------
if (Features::enabled('slideshow')) {
    $router->get ('/admin/slideshow',             [SlideshowController::class, 'index'],   ['auth' => true, 'role' => 'editor']);
    $router->get ('/admin/slideshow/new',         [SlideshowController::class, 'create'],  ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/slideshow',             [SlideshowController::class, 'store'],   ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/slideshow/reorder',     [SlideshowController::class, 'reorder'], ['auth' => true, 'role' => 'editor']);
    $router->get ('/admin/slideshow/{id}/edit',   [SlideshowController::class, 'edit'],    ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/slideshow/{id}',        [SlideshowController::class, 'update'],  ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/slideshow/{id}/toggle', [SlideshowController::class, 'toggle'],  ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/slideshow/{id}/delete', [SlideshowController::class, 'destroy'], ['auth' => true, 'role' => 'editor']);
}

// -------------------------------------------------------------------
// Staff Directory
// -------------------------------------------------------------------
if (Features::enabled('staff')) {
    $router->get('/staff', [PublicController::class, 'staff']);

    $router->get ('/admin/staff',              [StaffController::class, 'index'],   ['auth' => true, 'role' => 'editor']);
    $router->get ('/admin/staff/new',          [StaffController::class, 'create'],  ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/staff',              [StaffController::class, 'store'],   ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/staff/reorder',      [StaffController::class, 'reorder'], ['auth' => true, 'role' => 'editor']);
    $router->get ('/admin/staff/{id}/edit',    [StaffController::class, 'edit'],    ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/staff/{id}',         [StaffController::class, 'update'],  ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/staff/{id}/toggle',  [StaffController::class, 'toggle'],  ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/staff/{id}/delete',  [StaffController::class, 'destroy'], ['auth' => true, 'role' => 'editor']);
}

// -------------------------------------------------------------------
// Prayer Requests
//   Public form is no-auth. Admin inbox: editor+ for moderation,
//   admin-only for hard delete. Author role can hit /admin/prayer
//   (the controller hides private text from them).
// -------------------------------------------------------------------
if (Features::enabled('prayer')) {
    $router->get ('/prayer', [PrayerRequestController::class, 'publicForm']);
    $router->post('/prayer', [PrayerRequestController::class, 'submit']);

    $router->get ('/admin/prayer',                [PrayerRequestController::class, 'index'],        ['auth' => true]);
    $router->get ('/admin/prayer/{id}',           [PrayerRequestController::class, 'show'],         ['auth' => true]);
    $router->post('/admin/prayer/{id}/status',    [PrayerRequestController::class, 'updateStatus'], ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/prayer/{id}/delete',    [PrayerRequestController::class, 'destroy'],      ['auth' => true, 'role' => 'admin']);
}

// -------------------------------------------------------------------
// Contact Form
// -------------------------------------------------------------------
if (Features::enabled('contact')) {
    $router->get ('/contact', [ContactMessageController::class, 'publicForm']);
    $router->post('/contact', [ContactMessageController::class, 'submit']);

    $router->get ('/admin/contact',             [ContactMessageController::class, 'index'],      ['auth' => true]);
    $router->get ('/admin/contact/{id}',        [ContactMessageController::class, 'show'],       ['auth' => true]);
    $router->post('/admin/contact/{id}/read',   [ContactMessageController::class, 'markRead'],   ['auth' => true]);
    $router->post('/admin/contact/{id}/unread', [ContactMessageController::class, 'markUnread'], ['auth' => true]);
    $router->post('/admin/contact/{id}/delete', [ContactMessageController::class, 'destroy'],    ['auth' => true, 'role' => 'admin']);
}

// -------------------------------------------------------------------
// Menu (data-driven public navigation, roadmap #1.5)
//   Editor+ for moderation. Reorder JSON endpoint accepts the
//   CSRF token in the X-CSRF-Token header (the router supports
//   both _csrf field and that header).
// -------------------------------------------------------------------
if (Features::enabled('menu')) {
    $router->get ('/admin/menu',              [MenuController::class, 'index'],   ['auth' => true, 'role' => 'editor']);
    $router->get ('/admin/menu/new',          [MenuController::class, 'create'],  ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/menu',              [MenuController::class, 'store'],   ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/menu/reorder',      [MenuController::class, 'reorder'], ['auth' => true, 'role' => 'editor']);
    $router->get ('/admin/menu/{id}/edit',    [MenuController::class, 'edit'],    ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/menu/{id}',         [MenuController::class, 'update'],  ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/menu/{id}/toggle',  [MenuController::class, 'toggle'],  ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/menu/{id}/delete',  [MenuController::class, 'destroy'], ['auth' => true, 'role' => 'editor']);
}

$router->dispatch();
