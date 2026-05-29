<?php

namespace Mathieu\Cockpit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mathieu\Cockpit\Services\JobManager;
use Mathieu\Cockpit\Services\LogParser;

class CockpitController extends Controller
{
    public function __construct(
        private readonly JobManager $jobs,
        private readonly LogParser  $logs,
    ) {}

    public function index(Request $request)
    {
        $queueTable  = config('cockpit.queue_table', 'jobs');
        $failedTable = config('cockpit.failed_jobs_table', 'failed_jobs');
        $logFile     = config('cockpit.log_file', storage_path('logs/laravel.log'));

        $stats = [
            'pending_jobs' => $this->jobs->pendingCount($queueTable),
            'failed_jobs'  => $this->jobs->failedCount($failedTable),
        ];

        $recentErrors = $this->logs->recentErrors($logFile);
        $appInfo      = $this->gatherAppInfo();

        return view('cockpit::pages.dashboard', compact('stats', 'recentErrors', 'appInfo'));
    }

    private function gatherAppInfo(): array
    {
        return [
            'laravel_version' => app()->version(),
            'php_version'     => PHP_VERSION,
            'environment'     => app()->environment(),
            'debug'           => config('app.debug', false),
            'timezone'        => config('app.timezone', 'UTC'),
            'cache_driver'    => config('cache.default'),
            'queue_driver'    => config('queue.default'),
            'db_driver'       => config('database.default'),
            'cache_status'    => $this->checkCacheConnectivity(),
            'config_cached'   => file_exists(app()->getCachedConfigPath()),
            'routes_cached'   => file_exists(app()->getCachedRoutesPath()),
        ];
    }

    private function checkCacheConnectivity(): bool
    {
        try {
            cache()->store()->put('cockpit_ping', true, 5);
            return (bool) cache()->store()->get('cockpit_ping');
        } catch (\Throwable) {
            return false;
        }
    }
}
