<?php

/**
 * Route table entries (config/routes.php, collected by Registry::routes()):
 *   [METHOD, '/path/{id}', [ControllerClass::class, 'action'], 'page-key']
 * 'page-key' is optional; it enables the URL index.php?page=<page-key> (works on plain XAMPP and php -S)
 * and is exposed to layouts as $currentPage for the active menu link.
 * Links and form actions should use route('<page-key>') so they work with and without URL rewriting.
 */
final class Router
{
    public function __construct(private array $routes)
    {
    }

    public function dispatch(string $method, string $requestUri): void
    {
        $method = strtoupper($method);
        $query = [];
        parse_str((string) parse_url($requestUri, PHP_URL_QUERY), $query);

        // 1) ?page=<key>
        //    The key gives the path; the route with that path and the request method is called, so a form
        //    can POST to the same index.php?page=<key>. Path parameters come from the query string:
        //    /products/{id}  <->  index.php?page=product&id=5
        if (isset($query['page']) && is_string($query['page'])) {
            $path = null;
            foreach ($this->routes as $route) {
                if (($route[3] ?? null) === $query['page']) {
                    $path = $route[1];
                    break;
                }
            }
            if ($path === null) {
                abort(404);
            }
            foreach ($this->routes as $route) {
                if ($route[1] !== $path || $route[0] !== $method) {
                    continue;
                }
                preg_match_all('#\{(\w+)\}#', $path, $names);
                $params = [];
                foreach ($names[1] as $name) {
                    $params[$name] = isset($query[$name]) && is_scalar($query[$name]) ? (string) $query[$name] : null;
                }
                $this->call($route, $params, $query['page']);
                return;
            }
            abort(405);
        }

        // 2) clean path
        $path = $this->normalize((string) parse_url($requestUri, PHP_URL_PATH));
        $allowed = [];
        foreach ($this->routes as $route) {
            $params = $this->match($route[1], $path);
            if ($params === null) {
                continue;
            }
            if ($route[0] !== $method) {
                $allowed[] = $route[0];
                continue;
            }
            $this->call($route, $params, $route[3] ?? '');
            return;
        }
        if ($allowed) {
            header('Allow: ' . implode(', ', array_unique($allowed)));
            abort(405);
        }
        abort(404);
    }

    private function normalize(string $path): string
    {
        $base = base_path_url();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = preg_replace('#^/index\.php#', '', $path);
        return '/' . trim((string) $path, '/');
    }

    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern);
        if (!preg_match('#^' . $regex . '$#', $path, $m)) {
            return null;
        }
        return array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    private function call(array $route, array $params, string $pageKey): void
    {
        [, , $handler] = $route;
        [$class, $action] = $handler;
        View::setCurrentPage($pageKey);

        if (class_exists($class) && method_exists($class, $action)) {
            if (in_array(null, $params, true)) {
                abort(404); // a path parameter (e.g. id) is missing from the URL
            }
            (new $class())->$action(...array_values($params));
            return;
        }

        abort(501, "$class::$action is not implemented yet.");
    }
}
