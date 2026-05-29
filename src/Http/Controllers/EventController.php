<?php

namespace Mathieu\Cockpit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $events = $this->collectEvents();

        return view('cockpit::pages.events', compact('events'));
    }

    protected function collectEvents(): array
    {
        $dispatcher = app('events');
        $result     = [];

        try {
            $rawListeners = $dispatcher->getRawListeners();
        } catch (\Throwable) {
            return [];
        }

        ksort($rawListeners);

        foreach ($rawListeners as $event => $listeners) {
            $resolved = [];

            foreach ($listeners as $listener) {
                $resolved[] = $this->describeListener($listener);
            }

            $result[] = [
                'event'     => $event,
                'short'     => class_basename($event),
                'listeners' => $resolved,
                'count'     => count($resolved),
            ];
        }

        return $result;
    }

    protected function describeListener(mixed $listener): string
    {
        if (is_string($listener)) {
            return $listener;
        }

        if ($listener instanceof \Closure) {
            $ref = new \ReflectionFunction($listener);
            $file = basename($ref->getFileName() ?? '');
            $line = $ref->getStartLine();
            return "Closure ({$file}:{$line})";
        }

        if (is_array($listener)) {
            [$class, $method] = $listener;

            if (is_object($class)) {
                return get_class($class) . '@' . $method;
            }

            return $class . '@' . $method;
        }

        if (is_object($listener)) {
            return get_class($listener);
        }

        return 'Unknown listener';
    }
}
