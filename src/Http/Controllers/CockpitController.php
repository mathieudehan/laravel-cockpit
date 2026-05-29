<?php

namespace Mathieu\Cockpit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class CockpitController extends Controller
{
    public function index(Request $request)
    {
        $stats = $this->gatherStats();
        $recentErrors = $this->parseRecentErrors();
        $appInfo = $this->gatherAppInfo();

        return view('cockpit::pages.dashboard', compact('stats', 'recentErrors', 'appInfo'));
    }

    protected function gatherStats(): array
    {
        $queueTable      = config('cockpit.queue_table', 'jobs');
        $failedTable     = config('cockpit.failed_jobs_table', 'failed_jobs');
        $pendingJobs     = 0;
        $failedJobs      = 0;

        try {
            $pendingJobs = DB::table($queueTable)->count();
        } catch (\Throwable) {
        }

        try {
            $failedJobs = DB::table($failedTable)->count();
        } catch (\Throwable) {
        }

        return [
            'pending_jobs' => $pendingJobs,
            'failed_jobs'  => $failedJobs,
        ];
    }

    protected function parseRecentErrors(): array
    {
        $logFile = config('cockpit.log_file', storage_path('logs/laravel.log'));

        if (! file_exists($logFile)) {
            return [];
        }

        $errors  = [];
        $pattern = '/\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[^\]]*)\] \w+\.(ERROR|CRITICAL|EMERGENCY|ALERT)[^:]*: (.+)/';

        $lines = array_reverse(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));

        foreach ($lines as $line) {
            if (count($errors) >= 5) {
                break;
            }

            if (preg_match($pattern, $line, $matches)) {
                $errors[] = [
                    'date'    => $matches[1],
                    'level'   => $matches[2],
                    'message' => mb_strimwidth($matches[3], 0, 120, '…'),
                ];
            }
        }

        return $errors;
    }

    protected function gatherAppInfo(): array
    {
        $cacheStatus  = $this->checkCacheStatus();
        $configCached = file_exists(app()->getCachedConfigPath());
        $routesCached = file_exists(app()->getCachedRoutesPath());

        return [
            'laravel_version' => app()->version(),
            'php_version'     => PHP_VERSION,
            'environment'     => app()->environment(),
            'debug'           => config('app.debug', false),
            'timezone'        => config('app.timezone', 'UTC'),
            'cache_driver'    => config('cache.default'),
            'queue_driver'    => config('queue.default'),
            'db_driver'       => config('database.default'),
            'cache_status'    => $cacheStatus,
            'config_cached'   => $configCached,
            'routes_cached'   => $routesCached,
        ];
    }

    protected function checkCacheStatus(): bool
    {
        try {
            cache()->store()->put('cockpit_ping', true, 5);
            return (bool) cache()->store()->get('cockpit_ping');
        } catch (\Throwable) {
            return false;
        }
    }
}
