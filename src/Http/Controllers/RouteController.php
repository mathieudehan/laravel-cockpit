<?php

namespace Mathieu\Cockpit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mathieu\Cockpit\Services\RouteInspector;

class RouteController extends Controller
{
    public function __construct(private readonly RouteInspector $inspector) {}

    public function index(Request $request)
    {
        $routes = $this->inspector->collect();

        return view('cockpit::pages.routes', compact('routes'));
    }
}
