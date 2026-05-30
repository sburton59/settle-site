<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\Auth;

final class AuthController extends BaseController
{
    public function showLogin(): void
    {
        if (Auth::check()) { $this->redirect('/admin'); return; }
        $error = $_SESSION['_flash']['login_error'] ?? null;
        unset($_SESSION['_flash']['login_error']);
        $this->render('auth/login', [
            'error'  => $error,
            'return' => $_GET['return'] ?? '/admin',
        ], 'auth');
    }

    public function doLogin(): void
    {
        $username = trim((string)$this->input('username', ''));
        $password = (string)$this->input('password', '');
        $remember = (bool)$this->input('remember', false);
        $return   = (string)$this->input('return', '/admin');

        // Don't follow attacker-controlled absolute URLs
        if (!preg_match('#^/[^/]#', $return)) $return = '/admin';

        if (Auth::attempt($username, $password, $remember)) {
            $this->redirect($return);
            return;
        }
        $this->flash('login_error', 'Username or password not recognized.');
        $this->redirect('/admin/login');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/admin/login');
    }
}
