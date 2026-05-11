<?php
/**
 * Vision HR - Simple API Router
 * Maps HTTP method + URI pattern to controller actions
 */

class Router
{
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function get(string $pattern, callable $handler): self
    {
        return $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): self
    {
        return $this->addRoute('POST', $pattern, $handler);
    }

    public function put(string $pattern, callable $handler): self
    {
        return $this->addRoute('PUT', $pattern, $handler);
    }

    public function patch(string $pattern, callable $handler): self
    {
        return $this->addRoute('PATCH', $pattern, $handler);
    }

    public function delete(string $pattern, callable $handler): self
    {
        return $this->addRoute('DELETE', $pattern, $handler);
    }

    /**
     * Register a route
     */
    private function addRoute(string $method, string $pattern, callable $handler): self
    {
        $this->routes[] = [
            'method'  => $method,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
        return $this;
    }

    /**
     * Dispatch the current request
     */
    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $this->getUri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchPattern($route['pattern'], $uri);
            if ($params !== false) {
                call_user_func($route['handler'], $params);
                return;
            }
        }

        // Check if route exists with different method
        foreach ($this->routes as $route) {
            $params = $this->matchPattern($route['pattern'], $uri);
            if ($params !== false) {
                Response::methodNotAllowed();
            }
        }

        Response::notFound('المسار غير موجود: ' . $uri);
    }

    /**
     * Extract the URI path relative to the API base
     */
    private function getUri(): string
    {
        // Try route parameter first (from .htaccess rewrite)
        if (isset($_GET['route'])) {
            return '/' . trim($_GET['route'], '/');
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Remove base path (e.g., /HR/api/v1)
        $basePath = '/HR/api/v1';
        if (strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }

        return '/' . trim($uri, '/');
    }

    /**
     * Match a URI against a route pattern
     * Supports :param placeholders
     * Returns associative array of params or false
     */
    private function matchPattern(string $pattern, string $uri): array|false
    {
        $pattern = '/' . trim($pattern, '/');
        $uri = '/' . trim($uri, '/');

        // Exact match
        if ($pattern === $uri) {
            return [];
        }

        // Convert pattern to regex
        $patternParts = explode('/', $pattern);
        $uriParts = explode('/', $uri);

        if (count($patternParts) !== count($uriParts)) {
            return false;
        }

        $params = [];
        for ($i = 0; $i < count($patternParts); $i++) {
            if (str_starts_with($patternParts[$i], ':')) {
                // Named parameter
                $paramName = substr($patternParts[$i], 1);
                $params[$paramName] = urldecode($uriParts[$i]);
            } elseif ($patternParts[$i] !== $uriParts[$i]) {
                return false;
            }
        }

        return $params;
    }
}
