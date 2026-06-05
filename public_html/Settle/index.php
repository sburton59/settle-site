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
use Settle\Controller\CalendarOverrideController;
use Settle\Controller\CategoryController;
use Settle\Controller\ContactMessageController;
use Settle\Controller\DashboardController;
use Settle\Controller\MediaController;
use Settle\Controller\MenuController;
use Settle\Controller\PagesController;
use Settle\Controller\PostController;
use Settle\Controller\PrayerRequestController;
use Settle\Controller\PublicController;
use Settle\Controller\SettingsController;
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
// Settings (admin-only) — church identity, contact, notifications,
// worship times, social/app links, homepage copy, SEO meta, branding.
// Core admin, not an optional feature, so no Features flag gate.
// -------------------------------------------------------------------
$router->get ('/admin/settings', [SettingsController::class, 'edit'],   ['auth' => true, 'role' => 'admin']);
$router->post('/admin/settings', [SettingsController::class, 'update'], ['auth' => true, 'role' => 'admin']);

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
// Blog (multi-author posts + categories, roadmap #3)
//   Public: /blog listing, /blog/{slug} single post, /blog/category/{slug}
//   archive. Admin: posts are author-accessible but each post's OWNERSHIP
//   is enforced in-code in PostController (an author manages only their own
//   posts; editors+ manage any). The category list is editor+ only.
//   Everything is gated by the 'blog' flag, which also gates the sidebar
//   links and the /blog menu URL-picker entry.
// -------------------------------------------------------------------
if (Features::enabled('blog')) {
    // Public — order matters: the two-segment category route is registered
    // before the one-segment single-post route so /blog/category/{slug}
    // can't be swallowed by /blog/{slug}.
    $router->get('/blog',                 [PublicController::class, 'blog']);
    $router->get('/blog/category/{slug}', [PublicController::class, 'blogCategory']);
    $router->get('/blog/{slug}',          [PublicController::class, 'post']);

    // Posts — route gate is author+; per-post ownership checked in-code.
    $router->get ('/admin/posts',             [PostController::class, 'index'],     ['auth' => true, 'role' => 'author']);
    $router->get ('/admin/posts/new',         [PostController::class, 'create'],    ['auth' => true, 'role' => 'author']);
    $router->post('/admin/posts',             [PostController::class, 'store'],     ['auth' => true, 'role' => 'author']);
    $router->get ('/admin/posts/{id}/edit',   [PostController::class, 'edit'],      ['auth' => true, 'role' => 'author']);
    $router->post('/admin/posts/{id}',        [PostController::class, 'update'],    ['auth' => true, 'role' => 'author']);
    $router->post('/admin/posts/{id}/status', [PostController::class, 'setStatus'], ['auth' => true, 'role' => 'author']);
    $router->post('/admin/posts/{id}/delete', [PostController::class, 'destroy'],   ['auth' => true, 'role' => 'author']);

    // Categories — editor+ only (the curated list authors choose from).
    $router->get ('/admin/categories',             [CategoryController::class, 'index'],   ['auth' => true, 'role' => 'editor']);
    $router->get ('/admin/categories/new',         [CategoryController::class, 'create'],  ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/categories',             [CategoryController::class, 'store'],   ['auth' => true, 'role' => 'editor']);
    $router->get ('/admin/categories/{id}/edit',   [CategoryController::class, 'edit'],    ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/categories/{id}',        [CategoryController::class, 'update'],  ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/categories/{id}/delete', [CategoryController::class, 'destroy'], ['auth' => true, 'role' => 'editor']);
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
// Calendar (public events from the Google Calendar cache, roadmap #2)
//   Public month-grid page renders from the cron-synced cache
//   (bin/calendar-sync.php). Hide/feature are authored as [hide] /
//   [featured] tags in the Google Calendar event description. The admin
//   override editor (roadmap #4b) authors only the website-only image and
//   public note. Editor+; /calendar is already in MenuController's URL
//   picker (gated by the same flag).
// -------------------------------------------------------------------
if (Features::enabled('calendar')) {
    $router->get('/calendar', [PublicController::class, 'calendar']);

    $router->get ('/admin/calendar',                      [CalendarOverrideController::class, 'index'], ['auth' => true, 'role' => 'editor']);
    $router->get ('/admin/calendar/{id}/edit',            [CalendarOverrideController::class, 'edit'],  ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/calendar/{id}/override',        [CalendarOverrideController::class, 'save'],  ['auth' => true, 'role' => 'editor']);
    $router->post('/admin/calendar/{id}/override/delete', [CalendarOverrideController::class, 'clear'], ['auth' => true, 'role' => 'editor']);
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
