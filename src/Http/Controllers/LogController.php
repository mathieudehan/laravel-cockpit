<?php

namespace Mathieu\Cockpit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $logFile  = config('cockpit.log_file', storage_path('logs/laravel.log'));
        $perPage  = config('cockpit.log_lines_per_page', 100);
        $level    = $request->query('level', 'all');
        $page     = max(1, (int) $request->query('page', 1));

        $entries  = [];
        $total    = 0;
        $fileSize = 0;
        $exists   = file_exists($logFile);

        if ($exists) {
            $fileSize    = filesize($logFile);
            $allEntries  = $this->parseLog($logFile);

            if ($level !== 'all') {
                $allEntries = array_values(
                    array_filter($allEntries, fn($e) => strtolower($e['level']) === strtolower($level))
                );
            }

            $allEntries = array_reverse($allEntries);
            $total      = count($allEntries);
            $offset     = ($page - 1) * $perPage;
            $entries    = array_slice($allEntries, $offset, $perPage);
        }

        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

        return view('cockpit::pages.logs', compact(
            'entries', 'total', 'totalPages', 'page',
            'level', 'perPage', 'exists', 'fileSize'
        ));
    }

    public function clear(Request $request)
    {
        $logFile = config('cockpit.log_file', storage_path('logs/laravel.log'));

        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }

        return response()->json(['success' => true, 'message' => 'Log file cleared.']);
    }

    public function download(): BinaryFileResponse
    {
        $logFile = config('cockpit.log_file', storage_path('logs/laravel.log'));

        abort_unless(file_exists($logFile), 404, 'Log file not found.');

        return response()->download($logFile, 'laravel.log', [
            'Content-Type' => 'text/plain',
        ]);
    }

    protected function parseLog(string $file): array
    {
        $content = file_get_contents($file);

        if (empty($content)) {
            return [];
        }

        $entries = [];
        // Match each log entry header line
        $pattern = '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{2}:\d{2})?)\] (\w+)\.(DEBUG|INFO|NOTICE|WARNING|ERROR|CRITICAL|ALERT|EMERGENCY): (.+?)(\{.*\})?(\[.*\])?$/m';

        $lines = explode("\n", $content);
        $currentEntry = null;
        $contextLines = [];

        foreach ($lines as $line) {
            if (preg_match($pattern, $line, $m)) {
                if ($currentEntry !== null) {
                    $currentEntry['context'] = trim(implode("\n", $contextLines));
                    $entries[] = $currentEntry;
                }

                $currentEntry = [
                    'date'    => $m[1],
                    'channel' => $m[2],
                    'level'   => $m[3],
                    'message' => rtrim($m[4]),
                    'context' => '',
                ];
                $contextLines = [];

                $trailingJson = trim(($m[5] ?? '') . ' ' . ($m[6] ?? ''));
                if ($trailingJson !== '' && $trailingJson !== '[] []' && $trailingJson !== ' ') {
                    $contextLines[] = $trailingJson;
                }
            } elseif ($currentEntry !== null && trim($line) !== '') {
                $contextLines[] = $line;
            }
        }

        if ($currentEntry !== null) {
            $currentEntry['context'] = trim(implode("\n", $contextLines));
            $entries[] = $currentEntry;
        }

        return $entries;
    }
}
