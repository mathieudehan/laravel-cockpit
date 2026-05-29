<?php

namespace Mathieu\Cockpit\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CommandInspector
{
    private static bool $consoleLoaded = false;

    public function collect(): array
    {
        $this->ensureConsoleLoaded();

        $commands = [];

        foreach (Artisan::all() as $name => $command) {
            try {
                $definition  = $command->getDefinition();
                $arguments   = $this->extractArguments($definition);
                $options     = $this->extractOptions($definition);
                $parts       = explode(':', $name);
                $namespace   = count($parts) > 1 ? $parts[0] : '_global';

                $commands[$namespace][] = [
                    'name'        => $name,
                    'description' => $command->getDescription(),
                    'arguments'   => $arguments,
                    'options'     => $options,
                ];
            } catch (\Throwable $e) {
                Log::warning("[Cockpit] Could not inspect command [{$name}]: {$e->getMessage()}");
            }
        }

        ksort($commands);

        return $commands;
    }

    private function extractArguments(\Symfony\Component\Console\Input\InputDefinition $definition): array
    {
        $arguments = [];

        foreach ($definition->getArguments() as $arg) {
            $arguments[] = [
                'name'        => $arg->getName(),
                'description' => $arg->getDescription(),
                'required'    => $arg->isRequired(),
                'default'     => $arg->getDefault(),
            ];
        }

        return $arguments;
    }

    private function extractOptions(\Symfony\Component\Console\Input\InputDefinition $definition): array
    {
        $skip    = ['help', 'version', 'quiet', 'verbose', 'ansi', 'no-ansi', 'no-interaction', 'env'];
        $options = [];

        foreach ($definition->getOptions() as $opt) {
            if (in_array($opt->getName(), $skip, true)) {
                continue;
            }

            $options[] = [
                'name'          => $opt->getName(),
                'description'   => $opt->getDescription(),
                'default'       => $opt->getDefault(),
                'accepts_value' => $opt->acceptValue(),
            ];
        }

        return $options;
    }

    /**
     * In Laravel 11+, commands defined as closures in routes/console.php are
     * only registered when the console kernel boots — which never happens during
     * an HTTP request. Loading the file here registers them on the Artisan
     * application so they appear in the command list.
     */
    private function ensureConsoleLoaded(): void
    {
        if (self::$consoleLoaded) {
            return;
        }

        $path = base_path('routes/console.php');

        if (file_exists($path)) {
            try {
                require_once $path;
            } catch (\Throwable $e) {
                Log::warning("[Cockpit] Could not load routes/console.php: {$e->getMessage()}");
            }
        }

        self::$consoleLoaded = true;
    }
}
