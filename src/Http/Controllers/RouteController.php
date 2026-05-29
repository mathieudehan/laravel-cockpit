<?php

namespace Mathieu\Cockpit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;

class RouteController extends Controller
{
    public function index(Request $request)
    {
        $routes = $this->collectRoutes();

        return view('cockpit::pages.routes', compact('routes'));
    }

    protected function collectRoutes(): array
    {
        $routes = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getAction();

            $controller = 'Closure';
            if (isset($action['controller'])) {
                $controller = $action['controller'];
            } elseif (isset($action['uses']) && is_string($action['uses'])) {
                $controller = $action['uses'];
            }

            $middleware = $route->gatherMiddleware();

            $routes[] = [
                'methods'    => $route->methods(),
                'uri'        => $route->uri(),
                'name'       => $route->getName() ?? '',
                'action'     => $controller,
                'middleware' => $middleware,
            ];
        }

        usort($routes, fn($a, $b) => strcmp($a['uri'], $b['uri']));

        return $routes;
    }
}
