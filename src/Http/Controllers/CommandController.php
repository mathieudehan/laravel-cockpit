<?php

namespace Mathieu\Cockpit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Mathieu\Cockpit\Services\CommandInspector;

class CommandController extends Controller
{
    public function __construct(private readonly CommandInspector $inspector) {}

    public function index(Request $request)
    {
        $commands = $this->inspector->collect();
        $history  = session('cockpit_command_history', []);

        return view('cockpit::pages.commands', compact('commands', 'history'));
    }

    public function run(Request $request)
    {
        $validated = $request->validate([
            'command' => ['required', 'string', 'max:255'],
            'params'  => ['sometimes', 'array'],
        ]);

        $commandName = $validated['command'];
        $params      = $validated['params'] ?? [];

        $blockedPrefixes = config('cockpit.command_blocklist', [
            'db:wipe', 'migrate:fresh', 'migrate:reset', 'down',
        ]);

        foreach ($blockedPrefixes as $blocked) {
            if (str_starts_with($commandName, $blocked)) {
                return response()->json([
                    'success' => false,
                    'output'  => "Command [{$commandName}] is blocked for safety.",
                ], 403);
            }
        }

        $allCommands = $this->inspector->collect();
        $flat        = array_merge(...array_values($allCommands));
        $names       = array_column($flat, 'name');

        if (! in_array($commandName, $names, true)) {
            return response()->json([
                'success' => false,
                'output'  => "Command [{$commandName}] not found.",
            ], 404);
        }

        try {
            Artisan::call($commandName, $params);
            $output   = Artisan::output();
            $exitCode = 0;
        } catch (\Throwable $e) {
            $output   = $e->getMessage();
            $exitCode = 1;
        }

        $history = session('cockpit_command_history', []);
        array_unshift($history, [
            'command'   => $commandName,
            'params'    => $params,
            'output'    => $output ?: '(no output)',
            'exit_code' => $exitCode,
            'ran_at'    => now()->toDateTimeString(),
        ]);
        session(['cockpit_command_history' => array_slice($history, 0, 20)]);

        return response()->json([
            'success' => $exitCode === 0,
            'output'  => $output ?: '(no output)',
        ]);
    }

    public function clearHistory(Request $request)
    {
        session()->forget('cockpit_command_history');

        return response()->json(['success' => true]);
    }
}
