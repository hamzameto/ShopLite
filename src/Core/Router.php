<?php

namespace App\Core;

/**
 * Router
 *
 * This is the heart of the "front controller" pattern.
 *
 * WHAT it does: keeps a list of (HTTP method + URL pattern) -> code to run.
 * WHY it exists: without this, we'd need a separate PHP file for every single
 *                page/endpoint, and there'd be no central place to add things
 *                like authentication checks for ALL routes at once.
 */
class Router
{
    /** @var array<int, array{method:string, regex:string, handler:array|\Closure}> */
    private array $routes = [];

    /**
     * Register a route.
     *
     * $pattern can contain named parameters like /products/{id}
     * We convert that into a regex like #^/products/(?<id>[^/]+)$#
     *
     * $handler can be either:
     *   - a closure: fn($params) => ...
     *   - [ControllerClass::class, 'methodName']
     */
    public function add(string $method, string $pattern, array|\Closure $handler): void
    {
        // Turn {id} into a named capture group (?<id>[^/]+)
        $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        $this->routes[] = [
            'method'  => strtoupper($method),
            'regex'   => $regex,
            'handler' => $handler, // e.g. [ProductController::class, 'index'] or a closure
        ];
    }

    // Convenience shortcuts so route definitions read cleanly
    public function get(string $pattern, array|\Closure $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, array|\Closure $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function put(string $pattern, array|\Closure $handler): void
    {
        $this->add('PUT', $pattern, $handler);
    }

    public function delete(string $pattern, array|\Closure $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    /**
     * Look at the current request (method + URL) and run the matching handler.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $path, $matches)) {
                // Keep only the named parameters (e.g. 'id'), drop numeric keys
                $params = array_filter(
                    $matches,
                    fn($key) => !is_int($key),
                    ARRAY_FILTER_USE_KEY
                );

                $handler = $route['handler'];

                if ($handler instanceof \Closure) {
                    // Handler is a plain function, e.g. fn($params) => ...
                    // Useful for quick routes/tests without a full Controller class.
                    $handler($params);
                } else {
                    // Handler is [ControllerClass::class, 'methodName']
                    [$class, $methodName] = $handler;
                    $controller = new $class();
                    $controller->$methodName($params);
                }
                return;
            }
        }

        // No route matched at all -> 404
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not found']);
    }
}
