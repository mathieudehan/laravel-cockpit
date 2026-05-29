<?php

namespace Mathieu\Cockpit\Http\Controllers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $tasks = $this->collectTasks();

        return view('cockpit::pages.schedule', compact('tasks'));
    }

    public function run(Request $request)
    {
        $request->validate([
            'index' => 'required|integer|min:0',
        ]);

        $tasks = $this->collectRawEvents();
        $index = (int) $request->input('index');

        if (! isset($tasks[$index])) {
            return response()->json(['success' => false, 'message' => 'Task not found.'], 404);
        }

        $event = $tasks[$index];

        try {
            $event->run(app());
            return response()->json(['success' => true, 'message' => 'Task executed.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    protected function collectTasks(): array
    {
        $tasks = [];

        foreach ($this->collectRawEvents() as $index => $event) {
            $nextRun = $this->getNextRunDate($event->expression);

            $tasks[] = [
                'index'       => $index,
                'expression'  => $event->expression,
                'description' => $event->description ?? $this->resolveDescription($event),
                'command'     => $this->resolveDescription($event),
                'timezone'    => $event->timezone ?? config('app.timezone'),
                'next_run'    => $nextRun,
                'without_overlapping' => $event->withoutOverlapping ?? false,
                'runs_in_background'  => $event->runInBackground ?? false,
            ];
        }

        return $tasks;
    }

    protected function collectRawEvents(): array
    {
        try {
            $schedule = app(Schedule::class);
            return $schedule->events();
        } catch (\Throwable) {
            return [];
        }
    }

    protected function resolveDescription(object $event): string
    {
        // Artisan\CallbackEvent has $callback, Event has $command
        if (isset($event->command) && $event->command) {
            return ltrim(str_replace([PHP_BINARY, "'"], ['php', ''], $event->command));
        }

        if ($event->description) {
            return $event->description;
        }

        return 'Closure';
    }

    protected function getNextRunDate(string $expression): ?string
    {
        try {
            $cron = new \Cron\CronExpression($expression);
            return $cron->getNextRunDate()->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
