<?php

declare(strict_types = 1);

namespace App;

use App\Enums\{
    HttpStatusCode,
    HttpRequestMethod
};

final class Router
{
    /**
     * Registered application routes.
     *
     * Each HTTP request method maps to a collection of routes,
     * where each route is associated with a callable handler
     * or a class-method pair.
     *
     * @var array<
     *     string,
     *     array<
     *         string,
     *         array{0: class-string|object, 1: non-empty-string}|callable(): mixed
     *     >
     * >
     */

    private array $routes = [];

    /**
     * Registers a route and its corresponding handler for an HTTP method.
     *
     * @param HttpRequestMethod $requestMethod HTTP request method.
     * @param string $route Route URI.
     * @param array{0: class-string|object, 1: non-empty-string}|callable(): mixed $action Route handler.
     *
     * @return self|null Router instance for method chaining.
     */
    private function register(HttpRequestMethod $requestMethod, string $route, callable|array $action): ?self
    {
        $this->routes[$requestMethod()][$route] = $action;

        return $this;
    }

    public function get(string $route, callable|array $action): ?self
    {
        return $this->register(
            requestMethod: HttpRequestMethod::GET,
            route: $route,
            action: $action
        );
    }

    public function post(string $route, callable|array $action): ?self
    {
        return $this->register(
            requestMethod: HttpRequestMethod::POST,
            route: $route,
            action: $action
        );
    }

    /**
     * Resolves the requested URI and HTTP method, then executes its handler.
     *
     * The URI is parsed to extract its path, which is used together
     * with the HTTP method to locate the corresponding route handler.
     *
     * Handlers can be registered as directly callable values or
     * as class-method pairs.
     *
     * @param string $requestUri HTTP request URI.
     * @param string $requestMethod HTTP request method.
     *
     * @return mixed Result returned by the resolved route handler.
     *
     * @throws \Uri\InvalidUriException When the requested URI is malformed.
     * @throws \App\Exceptions\RouteNotFoundException When no route matches the requested URI and HTTP method.
     * @throws \BadMethodCallException When the registered handler cannot be invoked.
     */
    public function resolve(string $requestUri, string $requestMethod): mixed
    {
        $route = parse_url(
            url: $requestUri,
            component: PHP_URL_PATH
        ) ?? throw new \Uri\InvalidUriException(
                ...(HttpStatusCode::BAD_REQUEST)()
            );
        $action = $this->routes[$requestMethod][$route]
            ?? throw new \App\Exceptions\RouteNotFoundException(
                ...(HttpStatusCode::NOT_FOUND)()
            );

        if (is_callable(value: $action)) {
            return $action();
        }

        is_array(value: $action) and [$class, $method] = $action;
        class_exists(class: $class) and $class = new $class();
        is_object(value: $class)
        && method_exists(
            object_or_class: $class,
            method: $method
        ) and $result = $class?->$method();

        return $result ?? throw new \BadMethodCallException(
            ...(HttpStatusCode::NOT_IMPLEMENTED)()
        );
    }
}
