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
$router->get ('/admin/pages',            [PagesController::class, 'index'],      ['auth' => true]);
$router->get ('/admin/pages/new',        [PagesController::class, 'create'],     ['auth' => true, 'role' => 'editor']);
$router->post('/admin/pages',            [PagesController::class, 'store'],      ['auth' => true, 'role' => 'editor']);
$router->get ('/admin/pages/{id}/edit',  [PagesController::class, 'edit'],       ['auth' => true, 'role' => 'editor']);
$router->post('/admin/pages/{id}',       [PagesController::class, 'update'],     ['auth' => true, 'role' => 'editor']);
$router->post('/admin/pages/{id}/hide',  [PagesController::class, 'toggleHide'], ['auth' => true, 'role' => 'editor']);

$router->dispatch();