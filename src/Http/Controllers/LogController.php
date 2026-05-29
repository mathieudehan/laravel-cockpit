<?php

namespace Mathieu\Cockpit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mathieu\Cockpit\Services\LogParser;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogController extends Controller
{
    public function __construct(private readonly LogParser $parser) {}

    public function index(Request $request)
    {
        $logFile = config('cockpit.log_file', storage_path('logs/laravel.log'));
        $perPage = (int) config('cockpit.log_lines_per_page', 100);
        $level   = $request->query('level', 'all');
        $page    = max(1, (int) $request->query('page', 1));

        $result = $this->parser->paginate($logFile, $page, $perPage, $level);

        return view('cockpit::pages.logs', [
            'entries'    => $result['entries'],
            'total'      => $result['total'],
            'totalPages' => $result['total_pages'],
            'page'       => $page,
            'level'      => $level,
            'perPage'    => $perPage,
            'exists'     => $result['exists'],
            'fileSize'   => $result['file_size'],
            'truncated'  => $result['truncated'],
        ]);
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
}
