<?php

declare(strict_types = 1);

namespace App;

use App\Exceptions\RouteNotFoundException;

final class Router
{
    /**
     * Registered application routes.
     *
     * Each route is associated with a callable
     * that returns the response content.
     *
     * @var array<string, array|callable(): mixed>
     */

    private array $routes = [];

    /**
     * Registers a route and its corresponding handler.
     *
     * @param string $route Route URI.
     * @param array|callable(): string $action Route handler.
     *
     * @return self
     */
    public function register(string $route, callable|array $action): self
    {
        $this->routes[$route] = $action;
        return $this;
    }

    /**
     * Resolves the requested URI and executes its handler.
     *
     * @param string $requestUri HTTP request URI.
     *
     * @return mixed Handler response.
     *
     * @throws \Uri\InvalidUriException When the requested URI is malformed.
     * @throws RouteNotFoundException When the requested route does not exist.
     * @throws \BadMethodCallException When the requested method does not exist.
     */
    public function resolve(string $requestUri): mixed
    {
        $route = parse_url(
            url: $requestUri,
            component: PHP_URL_PATH
        ) ?? throw new \Uri\InvalidUriException(
            message: 'Bad Request',
            code: 400
        );
        $action = $this->routes[$route] ?? throw new RouteNotFoundException();

        if (is_callable(value: $action)) {
            return $action();
        }

        is_array(value: $action) and [$class, $method] = $action;
        class_exists(class: $class) and $class = new $class();
        method_exists(
            object_or_class: $class,
            method: $method
        ) and $result = $class?->$method();

        return $result ?? throw new \BadMethodCallException(
            message: 'Not Implemented',
            code: 501
        );
    }
}
