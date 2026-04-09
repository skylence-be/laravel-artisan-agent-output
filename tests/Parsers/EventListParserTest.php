<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Skylence\ArtisanAgentOutput\Parsers\EventListParser;

it('returns events as structured data', function () {
    $parser = new EventListParser;
    $result = $parser->parse($this->app);

    expect($result)->toHaveKeys(['total', 'events']);
    expect($result['events'])->toBeArray();
});

it('includes registered event listeners', function () {
    Event::listen('test.event', fn () => null);

    $parser = new EventListParser;
    $result = $parser->parse($this->app);

    $testEvent = collect($result['events'])->firstWhere('event', 'test.event');
    expect($testEvent)->not->toBeNull();
    expect($testEvent)->toHaveKeys(['event', 'listeners']);
    expect($testEvent['listeners'])->toBeArray();
});
