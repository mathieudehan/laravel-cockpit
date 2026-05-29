<?php

namespace Mathieu\Cockpit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mathieu\Cockpit\Services\EventInspector;

class EventController extends Controller
{
    public function __construct(private readonly EventInspector $inspector) {}

    public function index(Request $request)
    {
        $events = $this->inspector->collect();

        return view('cockpit::pages.events', compact('events'));
    }
}
