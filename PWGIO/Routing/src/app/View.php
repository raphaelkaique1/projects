<?php

declare(strict_types=1);

namespace App;

use App\Enums\HttpStatusCode;

class View
{

    public function __construct(
        protected string $view,
        protected array $params = []
    ) {}

    public static function render(string $view, array $params = []): View
    {
        return new static(
            view: $view,
            params: $params
        );
    }

    public function make(): string
    {
        $viewPath = VIEW_PATH . $this->view . '.php'
            ?: throw new \App\Exceptions\ViewNotFoundException(
                ...(HttpStatusCode::NOT_FOUND)()
            );

        extract(array: $this->params);

        ob_start();

        require_once $viewPath;

        return ob_get_clean();
    }

    public function __toString(): string
    {
        return $this->make();
    }
}
