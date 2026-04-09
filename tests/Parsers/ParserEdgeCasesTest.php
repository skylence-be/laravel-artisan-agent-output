<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\Parsers\ConfigShowParser;
use Skylence\ArtisanAgentOutput\Parsers\DbTableParser;
use Skylence\ArtisanAgentOutput\Parsers\EventListParser;
use Skylence\ArtisanAgentOutput\Parsers\MigrateStatusParser;
use Skylence\ArtisanAgentOutput\Parsers\QueueFailedParser;
use Skylence\ArtisanAgentOutput\Parsers\RouteListParser;
use Skylence\ArtisanAgentOutput\Parsers\ScheduleListParser;

it('MigrateStatusParser handles no migration repository', function () {
    // Fresh SQLite DB with no migrations table
    $parser = new MigrateStatusParser;
    $result = $parser->parse($this->app);

    expect($result['total'])->toBe(0);
    expect($result['ran'])->toBe(0);
    expect($result['pending'])->toBe(0);
    expect($result['migrations'])->toBe([]);
});

it('RouteListParser handles app with zero custom routes', function () {
    $parser = new RouteListParser;
    $result = $parser->parse($this->app);

    expect($result)->toHaveKeys(['total', 'routes']);
    expect($result['total'])->toBeInt();
    expect($result['routes'])->toBeArray();
});

it('ScheduleListParser handles empty schedule', function () {
    $parser = new ScheduleListParser;
    $result = $parser->parse($this->app);

    expect($result['total'])->toBe(0);
    expect($result['tasks'])->toBe([]);
});

it('EventListParser returns valid structure', function () {
    $parser = new EventListParser;
    $result = $parser->parse($this->app);

    expect($result)->toHaveKeys(['total', 'events']);
    expect($result['total'])->toBeInt();
    foreach ($result['events'] as $event) {
        expect($event)->toHaveKeys(['event', 'listeners']);
        expect($event['listeners'])->toBeArray();
    }
});

it('QueueFailedParser handles no failed jobs gracefully', function () {
    $parser = new QueueFailedParser;
    $result = $parser->parse($this->app);

    expect($result)->toBe(['total' => 0, 'jobs' => []]);
});

it('ConfigShowParser returns error when no key in argv', function () {
    $parser = new ConfigShowParser;
    $result = $parser->parse($this->app);

    expect($result)->toHaveKey('error');
});

it('DbTableParser returns error when no table in argv', function () {
    $parser = new DbTableParser;
    $result = $parser->parse($this->app);

    expect($result)->toHaveKey('error');
});

it('all parser JSON output is valid JSON', function () {
    $parsers = [
        new MigrateStatusParser,
        new RouteListParser,
        new ScheduleListParser,
        new EventListParser,
        new QueueFailedParser,
    ];

    foreach ($parsers as $parser) {
        $result = $parser->parse($this->app);
        $json = json_encode($result, JSON_THROW_ON_ERROR);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        expect($decoded)->toBe($result);
    }
});

it('RouteListParser omits domain key when no domain set', function () {
    Illuminate\Support\Facades\Route::get('/no-domain-test', fn () => 'ok');

    $parser = new RouteListParser;
    $result = $parser->parse($this->app);

    $route = collect($result['routes'])->firstWhere('uri', 'no-domain-test');
    expect($route)->not->toBeNull();
    expect($route)->not->toHaveKey('domain');
    expect($route)->not->toHaveKey('wheres');
    expect($route)->not->toHaveKey('without_middleware');
});

it('RouteListParser includes all conditional keys when present', function () {
    Illuminate\Support\Facades\Route::domain('{tenant}.test.com')
        ->middleware('web')
        ->get('/conditional/{slug}', fn () => 'ok')
        ->where('slug', '[a-z]+')
        ->name('conditional.test');

    $parser = new RouteListParser;
    $result = $parser->parse($this->app);

    $route = collect($result['routes'])->firstWhere('name', 'conditional.test');
    expect($route)->not->toBeNull();
    expect($route)->toHaveKey('domain');
    expect($route['domain'])->toBe('{tenant}.test.com');
    expect($route)->toHaveKey('wheres');
    expect($route['wheres'])->toHaveKey('slug');
});
