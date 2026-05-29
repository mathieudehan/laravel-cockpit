<?php

namespace Mathieu\Cockpit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $queueTable  = config('cockpit.queue_table', 'jobs');
        $failedTable = config('cockpit.failed_jobs_table', 'failed_jobs');

        $pendingJobs = $this->getPendingJobs($queueTable);
        $failedJobs  = $this->getFailedJobs($failedTable);
        $queueStats  = $this->getQueueStats($queueTable);

        return view('cockpit::pages.jobs', compact('pendingJobs', 'failedJobs', 'queueStats'));
    }

    public function retry(Request $request, string|int $id)
    {
        $failedTable = config('cockpit.failed_jobs_table', 'failed_jobs');

        $job = DB::table($failedTable)->find($id);

        if (! $job) {
            return response()->json(['success' => false, 'message' => 'Job not found.'], 404);
        }

        try {
            $this->retryFailedJob($job, $failedTable);
            return response()->json(['success' => true, 'message' => 'Job queued for retry.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, string|int $id)
    {
        $failedTable = config('cockpit.failed_jobs_table', 'failed_jobs');

        $deleted = DB::table($failedTable)->where('id', $id)->delete();

        if (! $deleted) {
            return response()->json(['success' => false, 'message' => 'Job not found.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Failed job deleted.']);
    }

    public function clearFailed(Request $request)
    {
        $failedTable = config('cockpit.failed_jobs_table', 'failed_jobs');

        DB::table($failedTable)->truncate();

        return response()->json(['success' => true, 'message' => 'All failed jobs cleared.']);
    }

    protected function getPendingJobs(string $table): array
    {
        try {
            return DB::table($table)
                ->orderBy('id')
                ->limit(100)
                ->get()
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    return [
                        'id'           => $job->id,
                        'queue'        => $job->queue,
                        'job_class'    => $payload['displayName'] ?? $payload['job'] ?? 'Unknown',
                        'attempts'     => $job->attempts,
                        'created_at'   => date('Y-m-d H:i:s', $job->created_at),
                        'available_at' => date('Y-m-d H:i:s', $job->available_at),
                        'reserved_at'  => $job->reserved_at ? date('Y-m-d H:i:s', $job->reserved_at) : null,
                    ];
                })
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    protected function getFailedJobs(string $table): array
    {
        try {
            return DB::table($table)
                ->orderByDesc('failed_at')
                ->limit(100)
                ->get()
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    return [
                        'id'         => $job->id,
                        'uuid'       => $job->uuid ?? null,
                        'queue'      => $job->queue,
                        'job_class'  => $payload['displayName'] ?? $payload['job'] ?? 'Unknown',
                        'exception'  => mb_strimwidth($job->exception ?? '', 0, 300, '…'),
                        'failed_at'  => $job->failed_at,
                    ];
                })
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    protected function getQueueStats(string $table): array
    {
        try {
            return DB::table($table)
                ->selectRaw('queue, count(*) as total')
                ->groupBy('queue')
                ->orderByDesc('total')
                ->get()
                ->keyBy('queue')
                ->map(fn($row) => $row->total)
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    protected function retryFailedJob(object $job, string $failedTable): void
    {
        $payload = json_decode($job->payload, true);

        if (empty($payload)) {
            throw new \RuntimeException('Invalid job payload.');
        }

        // Re-insert into the pending queue
        $queueTable = config('cockpit.queue_table', 'jobs');

        DB::table($queueTable)->insert([
            'queue'        => $job->queue,
            'payload'      => $job->payload,
            'attempts'     => 0,
            'reserved_at'  => null,
            'available_at' => time(),
            'created_at'   => time(),
        ]);

        // Remove from failed
        DB::table($failedTable)->where('id', $job->id)->delete();
    }
}
