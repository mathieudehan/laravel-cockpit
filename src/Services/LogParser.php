<?php

namespace Mathieu\Cockpit\Services;

use Illuminate\Support\Facades\Log;

class LogParser
{
    private const LOG_PATTERN = '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{2}:\d{2})?)\] (\w+)\.(DEBUG|INFO|NOTICE|WARNING|ERROR|CRITICAL|ALERT|EMERGENCY): (.+?)(\{.*\})?(\[.*\])?$/m';

    public function paginate(string $file, int $page, int $perPage, string $level): array
    {
        if (! file_exists($file)) {
            return $this->emptyResult($file, false);
        }

        $fileSize = filesize($file);

        try {
            $content = $this->readTail($file, config('cockpit.log_max_read_bytes', 20 * 1024 * 1024));
        } catch (\Throwable $e) {
            Log::warning("[Cockpit] Could not read log file: {$e->getMessage()}");
            return $this->emptyResult($file, true, $fileSize);
        }

        $allEntries = $this->parse($content);

        if ($level !== 'all') {
            $allEntries = array_values(
                array_filter($allEntries, static fn ($e) => strtolower($e['level']) === strtolower($level))
            );
        }

        $allEntries = array_reverse($allEntries);
        $total      = count($allEntries);
        $entries    = array_slice($allEntries, ($page - 1) * $perPage, $perPage);
        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

        return [
            'entries'     => $entries,
            'total'       => $total,
            'total_pages' => max(1, $totalPages),
            'file_size'   => $fileSize,
            'exists'      => true,
            'truncated'   => $fileSize > config('cockpit.log_max_read_bytes', 20 * 1024 * 1024),
        ];
    }

    public function recentErrors(string $file, int $limit = 5): array
    {
        if (! file_exists($file)) {
            return [];
        }

        $errors  = [];
        $pattern = '/\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[^\]]*)\] \w+\.(ERROR|CRITICAL|EMERGENCY|ALERT)[^:]*: (.+)/';

        try {
            $lines = array_reverse(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        } catch (\Throwable $e) {
            Log::warning("[Cockpit] Could not read log file for recent errors: {$e->getMessage()}");
            return [];
        }

        foreach ($lines as $line) {
            if (count($errors) >= $limit) {
                break;
            }

            if (preg_match($pattern, $line, $m)) {
                $errors[] = [
                    'date'    => $m[1],
                    'level'   => $m[2],
                    'message' => mb_strimwidth($m[3], 0, 120, '…'),
                ];
            }
        }

        return $errors;
    }

    private function parse(string $content): array
    {
        if (empty($content)) {
            return [];
        }

        $entries      = [];
        $currentEntry = null;
        $contextLines = [];

        foreach (explode("\n", $content) as $line) {
            if (preg_match(self::LOG_PATTERN, $line, $m)) {
                if ($currentEntry !== null) {
                    $currentEntry['context'] = trim(implode("\n", $contextLines));
                    $entries[]               = $currentEntry;
                }

                $currentEntry = [
                    'date'    => $m[1],
                    'channel' => $m[2],
                    'level'   => $m[3],
                    'message' => rtrim($m[4]),
                    'context' => '',
                ];

                $contextLines   = [];
                $trailingJson   = trim(($m[5] ?? '') . ' ' . ($m[6] ?? ''));

                if ($trailingJson !== '' && $trailingJson !== '[] []' && trim($trailingJson) !== '') {
                    $contextLines[] = $trailingJson;
                }
            } elseif ($currentEntry !== null && trim($line) !== '') {
                $contextLines[] = $line;
            }
        }

        if ($currentEntry !== null) {
            $currentEntry['context'] = trim(implode("\n", $contextLines));
            $entries[]               = $currentEntry;
        }

        return $entries;
    }

    /**
     * Read up to $maxBytes from the end of $file. Skips the first (likely
     * incomplete) line when the file is larger than the limit so we always
     * start on a clean log-entry boundary.
     */
    private function readTail(string $file, int $maxBytes): string
    {
        $size = filesize($file);

        if ($size === 0) {
            return '';
        }

        $handle = fopen($file, 'rb');

        if ($handle === false) {
            throw new \RuntimeException("Cannot open log file: {$file}");
        }

        try {
            if ($size > $maxBytes) {
                fseek($handle, -$maxBytes, SEEK_END);
                fgets($handle); // discard the potentially incomplete first line
            }

            return stream_get_contents($handle);
        } finally {
            fclose($handle);
        }
    }

    private function emptyResult(string $file, bool $exists, int $fileSize = 0): array
    {
        return [
            'entries'     => [],
            'total'       => 0,
            'total_pages' => 1,
            'file_size'   => $fileSize,
            'exists'      => $exists,
            'truncated'   => false,
        ];
    }
}
