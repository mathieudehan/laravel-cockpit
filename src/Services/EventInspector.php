<?php

namespace Mathieu\Cockpit\Services;

use Illuminate\Support\Facades\Log;
use ReflectionFunction;

class EventInspector
{
    public function collect(): array
    {
        try {
            $rawListeners = app('events')->getRawListeners();
        } catch (\Throwable $e) {
            Log::warning("[Cockpit] Could not collect event listeners: {$e->getMessage()}");
            return [];
        }

        ksort($rawListeners);

        $result = [];

        foreach ($rawListeners as $event => $listeners) {
            $resolved = array_map([$this, 'describeListener'], $listeners);

            $result[] = [
                'event'     => $event,
                'short'     => class_basename($event),
                'listeners' => $resolved,
                'count'     => count($resolved),
            ];
        }

        return $result;
    }

    private function describeListener(mixed $listener): string
    {
        if (is_string($listener)) {
            return $listener;
        }

        if ($listener instanceof \Closure) {
            try {
                $ref  = new ReflectionFunction($listener);
                $file = basename($ref->getFileName() ?? '');
                $line = $ref->getStartLine();
                return "Closure ({$file}:{$line})";
            } catch (\Throwable) {
                return 'Closure';
            }
        }

        if (is_array($listener)) {
            [$class, $method] = $listener;
            $className = is_object($class) ? get_class($class) : $class;
            return "{$className}@{$method}";
        }

        if (is_object($listener)) {
            return get_class($listener);
        }

        return 'Unknown listener';
    }
}
