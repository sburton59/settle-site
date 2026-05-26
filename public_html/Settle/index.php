<?php
declare(strict_types=1);

/**
 * Settle Memorial UMC — front controller.
 *
 * This is the ONLY PHP file inside the public web root.
 * Everything else lives in ../../settle-private/, outside public_html,
 * so it cannot be reached by any URL.
 */

require __DIR__ . '/../../settle-private/src/bootstrap.php';

use Settle\Router;
use Settle\Controller\AuthController;
use Settle\Controller\DashboardController;
use Settle\Controller\PagesController;
use Settle\Controller\MediaController;
use Settle\Controller\SlideshowController;
use Settle\Controller\PublicController;
use Settle\Controller\StaffController;
use Settle\Controller\PrayerRequestController;
use Settle\Controller\ContactMessageController;

$router = new Router();

// Public site
$router->get('/',              [PublicController::class, 'home']);
$router->get('/page/{slug}',   [PublicController::class, 'page']);

// Auth
$router->get ('/admin/login',  [AuthController::class, 'showLogin']);
$router->post('/admin/login',  [AuthController::class, 'doLogin']);
$router->post('/admin/logout', [AuthController::class, 'logout']);

// Admin (auth-protected)
$router->get ('/admin',                  [DashboardController::class, 'index'],  ['auth' => true]);

// Pages
$router->get ('/admin/pages',            [PagesController::class, 'index'],      ['auth' => true]);
$router->get ('/admin/pages/new',        [PagesController::class, 'create'],     ['auth' => true, 'role' => 'editor']);
$router->post('/admin/pages',            [PagesController::class, 'store'],      ['auth' => true, 'role' => 'editor']);
$router->get ('/admin/pages/{id}/edit',  [PagesController::class, 'edit'],       ['auth' => true, 'role' => 'editor']);
$router->post('/admin/pages/{id}',       [PagesController::class, 'update'],     ['auth' => true, 'role' => 'editor']);
$router->post('/admin/pages/{id}/hide',  [PagesController::class, 'toggleHide'], ['auth' => true, 'role' => 'editor']);

// Media Library — all authenticated users can browse and upload.
// Authors can only delete their own uploads (enforced inside the controller).
$router->get ('/admin/media',                       [MediaController::class, 'index'],            ['auth' => true]);
$router->post('/admin/media',                       [MediaController::class, 'upload'],           ['auth' => true]);
$router->get ('/admin/media/picker',                [MediaController::class, 'picker'],           ['auth' => true]);
$router->post('/admin/media/upload-from-editor',    [MediaController::class, 'uploadFromEditor'], ['auth' => true]);
$router->get ('/admin/media/{id}/edit',             [MediaController::class, 'edit'],             ['auth' => true]);
$router->post('/admin/media/{id}',                  [MediaController::class, 'update'],           ['auth' => true]);
$router->post('/admin/media/{id}/delete',           [MediaController::class, 'destroy'],          ['auth' => true]);

// Slideshow — editor+ only (it's homepage chrome).
$router->get ('/admin/slideshow',                 [SlideshowController::class, 'index'],    ['auth' => true, 'role' => 'editor']);
$router->get ('/admin/slideshow/new',             [SlideshowController::class, 'create'],   ['auth' => true, 'role' => 'editor']);
$router->post('/admin/slideshow',                 [SlideshowController::class, 'store'],    ['auth' => true, 'role' => 'editor']);
$router->post('/admin/slideshow/reorder',         [SlideshowController::class, 'reorder'],  ['auth' => true, 'role' => 'editor']);
$router->get ('/admin/slideshow/{id}/edit',       [SlideshowController::class, 'edit'],     ['auth' => true, 'role' => 'editor']);
$router->post('/admin/slideshow/{id}',            [SlideshowController::class, 'update'],   ['auth' => true, 'role' => 'editor']);
$router->post('/admin/slideshow/{id}/toggle',     [SlideshowController::class, 'toggle'],   ['auth' => true, 'role' => 'editor']);
$router->post('/admin/slideshow/{id}/delete',     [SlideshowController::class, 'destroy'],  ['auth' => true, 'role' => 'editor']);

// Public Staff page
$router->get('/staff', [PublicController::class, 'staff']);

// Staff Directory — editor+ only (it's website content).
$router->get ('/admin/staff',              [StaffController::class, 'index'],   ['auth' => true, 'role' => 'editor']);
$router->get ('/admin/staff/new',          [StaffController::class, 'create'],  ['auth' => true, 'role' => 'editor']);
$router->post('/admin/staff',              [StaffController::class, 'store'],   ['auth' => true, 'role' => 'editor']);
$router->post('/admin/staff/reorder',      [StaffController::class, 'reorder'], ['auth' => true, 'role' => 'editor']);
$router->get ('/admin/staff/{id}/edit',    [StaffController::class, 'edit'],    ['auth' => true, 'role' => 'editor']);
$router->post('/admin/staff/{id}',         [StaffController::class, 'update'],  ['auth' => true, 'role' => 'editor']);
$router->post('/admin/staff/{id}/toggle',  [StaffController::class, 'toggle'],  ['auth' => true, 'role' => 'editor']);
$router->post('/admin/staff/{id}/delete',  [StaffController::class, 'destroy'], ['auth' => true, 'role' => 'editor']);

// Public prayer request form (no auth — visitors submit anonymously)
$router->get ('/prayer', [PrayerRequestController::class, 'publicForm']);
$router->post('/prayer', [PrayerRequestController::class, 'submit']);

// Admin prayer inbox — editor+ for the inbox, admin-only for hard delete.
// Author role can hit /admin/prayer (the controller hides private text from them).
$router->get ('/admin/prayer',                [PrayerRequestController::class, 'index'],        ['auth' => true]);
$router->get ('/admin/prayer/{id}',           [PrayerRequestController::class, 'show'],         ['auth' => true]);
$router->post('/admin/prayer/{id}/status',    [PrayerRequestController::class, 'updateStatus'], ['auth' => true, 'role' => 'editor']);
$router->post('/admin/prayer/{id}/delete',    [PrayerRequestController::class, 'destroy'],      ['auth' => true, 'role' => 'admin']);

// Contact form — public intake + admin inbox.
$router->get ('/contact',                        [ContactMessageController::class, 'publicForm']);
$router->post('/contact',                        [ContactMessageController::class, 'submit']);
$router->get ('/admin/contact',                  [ContactMessageController::class, 'index'],      ['auth' => true]);
$router->get ('/admin/contact/{id}',             [ContactMessageController::class, 'show'],       ['auth' => true]);
$router->post('/admin/contact/{id}/read',        [ContactMessageController::class, 'markRead'],   ['auth' => true]);
$router->post('/admin/contact/{id}/unread',      [ContactMessageController::class, 'markUnread'], ['auth' => true]);
$router->post('/admin/contact/{id}/delete',      [ContactMessageController::class, 'destroy'],    ['auth' => true, 'role' => 'admin']);

$router->dispatch();
