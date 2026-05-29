<?php

namespace Mathieu\Cockpit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mathieu\Cockpit\Services\JobManager;

class JobController extends Controller
{
    public function __construct(private readonly JobManager $manager) {}

    public function index(Request $request)
    {
        $queueTable  = config('cockpit.queue_table', 'jobs');
        $failedTable = config('cockpit.failed_jobs_table', 'failed_jobs');

        return view('cockpit::pages.jobs', [
            'pendingJobs' => $this->manager->pendingJobs($queueTable),
            'failedJobs'  => $this->manager->failedJobs($failedTable),
            'queueStats'  => $this->manager->queueStats($queueTable),
        ]);
    }

    public function retry(Request $request, string|int $id)
    {
        $queueTable  = config('cockpit.queue_table', 'jobs');
        $failedTable = config('cockpit.failed_jobs_table', 'failed_jobs');

        try {
            $this->manager->retry($id, $failedTable, $queueTable);
            return response()->json(['success' => true, 'message' => 'Job queued for retry.']);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred.'], 500);
        }
    }

    public function destroy(Request $request, string|int $id)
    {
        $failedTable = config('cockpit.failed_jobs_table', 'failed_jobs');

        try {
            $this->manager->deleteFailedJob($id, $failedTable);
            return response()->json(['success' => true, 'message' => 'Failed job deleted.']);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    public function clearFailed(Request $request)
    {
        $failedTable = config('cockpit.failed_jobs_table', 'failed_jobs');

        $this->manager->clearAllFailedJobs($failedTable);

        return response()->json(['success' => true, 'message' => 'All failed jobs cleared.']);
    }
}
