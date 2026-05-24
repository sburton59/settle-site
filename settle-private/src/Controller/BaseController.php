<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\View;
use Settle\Auth;

abstract class BaseController
{
    protected function render(string $template, array $data = [], ?string $layout = 'admin'): void
    {
        $data['_user'] = Auth::user();
        View::render($template, $data, $layout);
    }

    protected function redirect(string $to): void
    {
        header('Location: ' . $to);
        exit;
    }

    protected function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    protected function input(string $name, $default = null)
    {
        return $_POST[$name] ?? $_GET[$name] ?? $default;
    }
}