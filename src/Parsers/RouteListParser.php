<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class RouteListParser implements CommandParser
{
    public function parse(Application $app): array
    {
        /** @var Router $router */
        $router = $app->make(Router::class);
        $routes = $router->getRoutes();

        $result = [];
        foreach ($routes as $route) {
            $result[] = [
                'method' => implode('|', $route->methods()),
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
                'middleware' => $router->gatherRouteMiddleware($route),
            ];
        }

        return [
            'total' => count($result),
            'routes' => $result,
        ];
    }
}
