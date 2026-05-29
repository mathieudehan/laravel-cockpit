<?php

namespace Mathieu\Cockpit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CommandController extends Controller
{
    public function index(Request $request)
    {
        $commands = $this->collectCommands();
        $history  = session('cockpit_command_history', []);

        return view('cockpit::pages.commands', compact('commands', 'history'));
    }

    public function run(Request $request)
    {
        $request->validate([
            'command' => 'required|string',
        ]);

        $commandName = $request->input('command');
        $params      = $request->input('params', []);

        // Security: block destructive system commands
        $blockedPrefixes = ['db:wipe', 'migrate:fresh', 'migrate:reset', 'down'];
        foreach ($blockedPrefixes as $blocked) {
            if (str_starts_with($commandName, $blocked)) {
                return response()->json([
                    'success' => false,
                    'output'  => "Command [{$commandName}] is blocked for safety.",
                ], 403);
            }
        }

        $allCommands = Artisan::all();
        if (! isset($allCommands[$commandName])) {
            return response()->json([
                'success' => false,
                'output'  => "Command [{$commandName}] not found.",
            ], 404);
        }

        try {
            Artisan::call($commandName, $params);
            $output = Artisan::output();
            $exitCode = 0;
        } catch (\Throwable $e) {
            $output   = $e->getMessage();
            $exitCode = 1;
        }

        $history = session('cockpit_command_history', []);
        array_unshift($history, [
            'command'   => $commandName,
            'params'    => $params,
            'output'    => $output,
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

    protected function collectCommands(): array
    {
        $commands = [];

        foreach (Artisan::all() as $name => $command) {
            $definition = $command->getDefinition();

            $arguments = [];
            foreach ($definition->getArguments() as $arg) {
                $arguments[] = [
                    'name'        => $arg->getName(),
                    'description' => $arg->getDescription(),
                    'required'    => $arg->isRequired(),
                    'default'     => $arg->getDefault(),
                ];
            }

            $options = [];
            foreach ($definition->getOptions() as $opt) {
                // Skip the ubiquitous --help, --version, --quiet etc.
                if (in_array($opt->getName(), ['help', 'version', 'quiet', 'verbose', 'ansi', 'no-ansi', 'no-interaction', 'env'], true)) {
                    continue;
                }
                $options[] = [
                    'name'        => $opt->getName(),
                    'description' => $opt->getDescription(),
                    'default'     => $opt->getDefault(),
                    'accepts_value' => $opt->acceptValue(),
                ];
            }

            $parts = explode(':', $name);
            $namespace = count($parts) > 1 ? $parts[0] : '_global';

            $commands[$namespace][] = [
                'name'        => $name,
                'description' => $command->getDescription(),
                'arguments'   => $arguments,
                'options'     => $options,
            ];
        }

        ksort($commands);

        return $commands;
    }
}
