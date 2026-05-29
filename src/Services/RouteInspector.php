<?php

namespace Mathieu\Cockpit\Services;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

class RouteInspector
{
    public function collect(): array
    {
        $routes = [];

        foreach (RouteFacade::getRoutes() as $route) {
            $routes[] = [
                'methods'    => $route->methods(),
                'uri'        => $route->uri(),
                'name'       => $route->getName() ?? '',
                'action'     => $this->resolveAction($route),
                'middleware' => $route->gatherMiddleware(),
            ];
        }

        usort($routes, static fn ($a, $b) => strcmp($a['uri'], $b['uri']));

        return $routes;
    }

    private function resolveAction(Route $route): string
    {
        $action = $route->getAction();

        if (isset($action['controller'])) {
            return $action['controller'];
        }

        if (isset($action['uses']) && is_string($action['uses'])) {
            return $action['uses'];
        }

        return 'Closure';
    }
}
