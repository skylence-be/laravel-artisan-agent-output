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
        foreach ($rawListeners as $event => $listeners) {
            $resolved = [];
            foreach ($listeners as $listener) {
                if (is_string($listener)) {
                    $resolved[] = $listener;
                } elseif (is_array($listener) && count($listener) === 2) {
                    $resolved[] = (is_object($listener[0]) ? $listener[0]::class : $listener[0]).'@'.$listener[1];
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
