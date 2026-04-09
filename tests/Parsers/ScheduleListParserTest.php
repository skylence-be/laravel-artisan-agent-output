<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;
use Skylence\ArtisanAgentOutput\Parsers\ScheduleListParser;

it('returns empty schedule when no tasks registered', function () {
    $parser = new ScheduleListParser;
    $result = $parser->parse($this->app);

    expect($result)->toHaveKeys(['total', 'tasks']);
    expect($result['total'])->toBe(0);
    expect($result['tasks'])->toBe([]);
});

it('returns scheduled tasks as structured data', function () {
    $schedule = $this->app->make(Schedule::class);
    $schedule->command('inspire')->daily();
    $schedule->command('cache:clear')->hourly();

    $parser = new ScheduleListParser;
    $result = $parser->parse($this->app);

    expect($result['total'])->toBe(2);
    expect($result['tasks'])->toHaveCount(2);

    $first = $result['tasks'][0];
    expect($first)->toHaveKeys(['command', 'expression', 'description', 'next_run']);
});
