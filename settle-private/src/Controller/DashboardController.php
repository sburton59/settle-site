<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\Auth;
use Settle\RateLimiter;

final class DashboardController extends BaseController
{
    public function index(): void
    {
        // Surface a degraded login throttle (e.g. the login_attempts table
        // missing) to ADMINS ONLY. The hot path fails open and silently, so
        // without this a broken limiter goes unnoticed; non-admins can't fix
        // it, so they don't see the warning and we skip the probe entirely.
        // null = "not checked" (non-admin); false = "checked and broken".
        $rateLimiterOk = Auth::hasRole('admin') ? RateLimiter::healthy() : null;

        $this->render('admin/dashboard', [
            'rate_limiter_ok' => $rateLimiterOk,
        ]);
    }
}
