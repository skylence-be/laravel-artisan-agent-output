<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Cron\CronExpression;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Carbon;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class ScheduleListParser implements CommandParser
{
    public function parse(Application $app): array
    {
        /** @var Schedule $schedule */
        $schedule = $app->make(Schedule::class);
        $events = $schedule->events();
        $timezone = config('app.timezone', 'UTC');

        $tasks = [];
        foreach ($events as $event) {
            $nextRun = null;

            try {
                $nextRun = (new CronExpression($event->expression))
                    ->getNextRunDate(Carbon::now()->setTimezone($event->timezone ?? $timezone))
                    ->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                // Invalid expression
            }

            $tasks[] = [
                'command' => $event->getSummaryForDisplay(),
                'expression' => $event->expression,
                'description' => $event->description ?? '',
                'next_run' => $nextRun,
            ];
        }

        return [
            'total' => count($tasks),
            'tasks' => $tasks,
        ];
    }
}
