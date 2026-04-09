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
        $routes = $router->getRoutes()->getRoutes();

        $result = [];
        foreach ($routes as $route) {
            /** @var list<string> $methods */
            $methods = $route->methods();
            $entry = [
                'method' => implode('|', $methods),
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
                'middleware' => $router->gatherRouteMiddleware($route),
            ];

            $domain = $route->getDomain();
            if ($domain !== null) {
                $entry['domain'] = $domain;
            }

            $wheres = $route->wheres;
            if ($wheres !== []) {
                $entry['wheres'] = $wheres;
            }

            $excludedMiddleware = $route->excludedMiddleware();
            if ($excludedMiddleware !== []) {
                $entry['without_middleware'] = $excludedMiddleware;
            }

            $result[] = $entry;
        }

        return [
            'total' => count($result),
            'routes' => $result,
        ];
    }
}
