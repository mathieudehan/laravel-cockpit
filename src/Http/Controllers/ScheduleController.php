<?php

namespace Mathieu\Cockpit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mathieu\Cockpit\Services\ScheduleInspector;

class ScheduleController extends Controller
{
    public function __construct(private readonly ScheduleInspector $inspector) {}

    public function index(Request $request)
    {
        $tasks = $this->inspector->collect();

        return view('cockpit::pages.schedule', compact('tasks'));
    }

    public function run(Request $request)
    {
        $validated = $request->validate([
            'index' => ['required', 'integer', 'min:0'],
        ]);

        $events = $this->inspector->rawEvents();
        $index  = (int) $validated['index'];

        if (! isset($events[$index])) {
            return response()->json(['success' => false, 'message' => 'Task not found.'], 404);
        }

        try {
            $events[$index]->run(app());
            return response()->json(['success' => true, 'message' => 'Task executed successfully.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
