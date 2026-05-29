<?php

namespace Mathieu\Cockpit\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JobManager
{
    public function pendingJobs(string $table): array
    {
        try {
            return DB::table($table)
                ->orderBy('id')
                ->limit(100)
                ->get()
                ->map(static function ($job) {
                    $payload = json_decode($job->payload, true) ?? [];
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
                ->all();
        } catch (\Throwable $e) {
            Log::warning("[Cockpit] Could not read pending jobs from [{$table}]: {$e->getMessage()}");
            return [];
        }
    }

    public function failedJobs(string $table): array
    {
        try {
            return DB::table($table)
                ->orderByDesc('failed_at')
                ->limit(100)
                ->get()
                ->map(static function ($job) {
                    $payload = json_decode($job->payload, true) ?? [];
                    return [
                        'id'        => $job->id,
                        'uuid'      => $job->uuid ?? null,
                        'queue'     => $job->queue,
                        'job_class' => $payload['displayName'] ?? $payload['job'] ?? 'Unknown',
                        'exception' => mb_strimwidth($job->exception ?? '', 0, 300, '…'),
                        'failed_at' => $job->failed_at,
                    ];
                })
                ->all();
        } catch (\Throwable $e) {
            Log::warning("[Cockpit] Could not read failed jobs from [{$table}]: {$e->getMessage()}");
            return [];
        }
    }

    public function queueStats(string $table): array
    {
        try {
            return DB::table($table)
                ->selectRaw('queue, count(*) as total')
                ->groupBy('queue')
                ->orderByDesc('total')
                ->get()
                ->keyBy('queue')
                ->map(static fn ($row) => $row->total)
                ->all();
        } catch (\Throwable $e) {
            Log::warning("[Cockpit] Could not compute queue stats from [{$table}]: {$e->getMessage()}");
            return [];
        }
    }

    public function pendingCount(string $table): int
    {
        try {
            return DB::table($table)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function failedCount(string $table): int
    {
        try {
            return DB::table($table)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function retry(string|int $id, string $failedTable, string $queueTable): void
    {
        $job = DB::table($failedTable)->find($id);

        if (! $job) {
            throw new \RuntimeException("Failed job [{$id}] not found.");
        }

        $payload = json_decode($job->payload, true);

        if (empty($payload)) {
            throw new \RuntimeException("Job [{$id}] has an invalid payload and cannot be retried.");
        }

        DB::transaction(function () use ($job, $payload, $failedTable, $queueTable) {
            DB::table($queueTable)->insert([
                'queue'        => $job->queue,
                'payload'      => $job->payload,
                'attempts'     => 0,
                'reserved_at'  => null,
                'available_at' => time(),
                'created_at'   => time(),
            ]);

            DB::table($failedTable)->where('id', $job->id)->delete();
        });

        Log::info("[Cockpit] Failed job [{$id}] ({$payload['displayName']}) retried.");
    }

    public function deleteFailedJob(string|int $id, string $failedTable): void
    {
        $deleted = DB::table($failedTable)->where('id', $id)->delete();

        if (! $deleted) {
            throw new \RuntimeException("Failed job [{$id}] not found.");
        }
    }

    public function clearAllFailedJobs(string $failedTable): void
    {
        DB::table($failedTable)->truncate();
        Log::info("[Cockpit] All failed jobs cleared from [{$failedTable}].");
    }
}
