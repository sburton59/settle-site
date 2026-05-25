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

$router->dispatch();
