<?php

namespace Mathieu\Cockpit\Services;

use Cron\CronExpression;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;

class ScheduleInspector
{
    private static bool $consoleLoaded = false;

    public function collect(): array
    {
        $tasks = [];

        foreach ($this->rawEvents() as $index => $event) {
            $tasks[] = [
                'index'               => $index,
                'expression'          => $event->expression,
                'description'         => $event->description ?? $this->resolveDescription($event),
                'command'             => $this->resolveDescription($event),
                'timezone'            => $event->timezone ?? config('app.timezone'),
                'next_run'            => $this->nextRunDate($event->expression),
                'without_overlapping' => $event->withoutOverlapping ?? false,
                'runs_in_background'  => $event->runInBackground ?? false,
            ];
        }

        return $tasks;
    }

    /** @return \Illuminate\Console\Scheduling\Event[] */
    public function rawEvents(): array
    {
        $this->ensureConsoleLoaded();

        try {
            return app(Schedule::class)->events();
        } catch (\Throwable $e) {
            Log::warning("[Cockpit] Could not collect scheduled tasks: {$e->getMessage()}");
            return [];
        }
    }

    private function resolveDescription(object $event): string
    {
        if (! empty($event->description)) {
            return $event->description;
        }

        if (! empty($event->command)) {
            return ltrim(str_replace([PHP_BINARY, "'"], ['php', ''], $event->command));
        }

        return 'Closure';
    }

    private function nextRunDate(string $expression): ?string
    {
        try {
            return (new CronExpression($expression))->getNextRunDate()->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * In Laravel 11+, scheduled tasks are defined in routes/console.php which
     * is only loaded during console bootstrap — not during HTTP requests.
     */
    private function ensureConsoleLoaded(): void
    {
        if (self::$consoleLoaded) {
            return;
        }

        $path = base_path('routes/console.php');

        if (file_exists($path)) {
            try {
                require_once $path;
            } catch (\Throwable $e) {
                Log::warning("[Cockpit] Could not load routes/console.php: {$e->getMessage()}");
            }
        }

        self::$consoleLoaded = true;
    }
}
