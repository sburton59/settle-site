<?php
declare(strict_types=1);
namespace Settle;

/**
 * Tiny regex-based router. Supports {param} placeholders, GET/POST,
 * and per-route 'auth' + 'role' middleware. CSRF is enforced on all POSTs.
 */
final class Router
{
    /** @var array<int, array{method:string, pattern:string, names:array, handler:array, opts:array}> */
    private array $routes = [];

    public function get(string $path, array $handler, array $opts = []): void
    {
        $this->add('GET', $path, $handler, $opts);
    }

    public function post(string $path, array $handler, array $opts = []): void
    {
        $this->add('POST', $path, $handler, $opts);
    }

    private function add(string $method, string $path, array $handler, array $opts): void
    {
        $names = [];
        $pattern = preg_replace_callback('#\{([a-zA-Z_]+)\}#', function ($m) use (&$names) {
            $names[] = $m[1];
            return '([^/]+)';
        }, $path);
        $this->routes[] = [
            'method'  => $method,
            'pattern' => '#^' . $pattern . '$#',
            'names'   => $names,
            'handler' => $handler,
            'opts'    => $opts,
        ];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            if (!preg_match($route['pattern'], $uri, $matches)) continue;

            // Middleware: auth
            if (!empty($route['opts']['auth']) && !Auth::check()) {
                header('Location: /admin/login?return=' . urlencode($uri));
                return;
            }
            // Middleware: role
            if (!empty($route['opts']['role']) && !Auth::hasRole($route['opts']['role'])) {
                http_response_code(403);
                echo 'Forbidden.';
                return;
            }
            // CSRF on every POST
            if ($method === 'POST' && !Csrf::verify((string)($_POST['_csrf'] ?? ''))) {
                http_response_code(419);
                echo 'Session expired. Please go back and try again.';
                return;
            }

            // Build named params from regex matches
            $params = [];
            foreach ($route['names'] as $i => $name) {
                $params[$name] = $matches[$i + 1] ?? null;
            }

            [$class, $action] = $route['handler'];
            $controller = new $class();
            $controller->$action($params);
            return;
        }

        http_response_code(404);
        echo 'Page not found.';
    }
}