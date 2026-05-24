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
use Settle\Controller\PublicController;

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
$router->get ('/admin/media',                 [MediaController::class, 'index'],   ['auth' => true]);
$router->post('/admin/media',                 [MediaController::class, 'upload'],  ['auth' => true]);
$router->get ('/admin/media/{id}/edit',       [MediaController::class, 'edit'],    ['auth' => true]);
$router->post('/admin/media/{id}',            [MediaController::class, 'update'],  ['auth' => true]);
$router->post('/admin/media/{id}/delete',     [MediaController::class, 'destroy'], ['auth' => true]);

$router->dispatch();
