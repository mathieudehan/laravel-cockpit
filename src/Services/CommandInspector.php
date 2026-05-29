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
     * In Laravel 11+, commands are not registered during HTTP requests:
     *  - routes/console.php (closure commands) is only loaded by the console kernel
     *  - app/Console/Commands/ classes are only auto-discovered at console boot time
     *
     * We force-load both here so that all commands appear in the Cockpit UI.
     */
    private function ensureConsoleLoaded(): void
    {
        if (self::$consoleLoaded) {
            return;
        }

        self::$consoleLoaded = true;

        // 1. Load closure commands from routes/console.php
        $consolePath = base_path('routes/console.php');
        if (file_exists($consolePath)) {
            try {
                require_once $consolePath;
            } catch (\Throwable $e) {
                Log::warning("[Cockpit] Could not load routes/console.php: {$e->getMessage()}");
            }
        }

        // 2. Auto-discover class-based commands from app/Console/Commands/
        $commandsPath = app_path('Console/Commands');
        if (! is_dir($commandsPath)) {
            return;
        }

        try {
            $finder    = new \Symfony\Component\Finder\Finder();
            $namespace = app()->getNamespace();
            $appPath   = realpath(app_path()) . DIRECTORY_SEPARATOR;

            foreach ($finder->files()->name('*.php')->in($commandsPath) as $file) {
                $class = $namespace . str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    \Illuminate\Support\Str::after($file->getRealPath(), $appPath)
                );

                if (
                    class_exists($class) &&
                    is_subclass_of($class, \Symfony\Component\Console\Command\Command::class) &&
                    ! (new \ReflectionClass($class))->isAbstract()
                ) {
                    Artisan::resolve($class);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("[Cockpit] Could not auto-discover commands from app/Console/Commands: {$e->getMessage()}");
        }
    }
}
