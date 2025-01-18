<?php

declare(strict_types=1);

namespace BeastBytes\Router\Register\Attribute\Method;

use BeastBytes\Router\Register\Attribute\Route;
use BeastBytes\Router\Register\RouteInterface;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class Get extends Route
{
    public function __construct(
        RouteInterface $route,
        array|callable|string $middleware = [],
        array|callable|string $disableMiddleware = [],
    )
    {
        parent::__construct(
            methods: [Method::GET],
            route: $route,
            middleware: $middleware,
            disableMiddleware: $disableMiddleware
        );
    }
}