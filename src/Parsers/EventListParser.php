<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class EventListParser implements CommandParser
{
    public function parse(Application $app): array
    {
        /** @var \Illuminate\Events\Dispatcher $dispatcher */
        $dispatcher = $app->make('events');
        $rawListeners = $dispatcher->getRawListeners();

        $events = [];
        /** @var array<string, array<int, mixed>> $rawListeners */
        foreach ($rawListeners as $event => $listeners) {
            $resolved = [];
            foreach ($listeners as $listener) {
                if (is_string($listener)) {
                    $resolved[] = $listener;
                } elseif (is_array($listener) && count($listener) === 2) {
                    $first = $listener[0];
                    $second = $listener[1];
                    $className = is_object($first) ? $first::class : (is_string($first) ? $first : '');
                    $methodName = is_string($second) ? $second : '';
                    $resolved[] = $className.'@'.$methodName;
                } else {
                    $resolved[] = 'Closure';
                }
            }

            $events[] = [
                'event' => $event,
                'listeners' => $resolved,
            ];
        }

        return [
            'total' => count($events),
            'events' => $events,
        ];
    }
}
