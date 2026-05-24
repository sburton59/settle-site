<?php
declare(strict_types=1);
namespace Settle\Controller;

final class DashboardController extends BaseController
{
    public function index(): void
    {
        $this->render('admin/dashboard');
    }
}