<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\Parsers\MigrateStatusParser;
use Skylence\ArtisanAgentOutput\Parsers\RouteListParser;

it('produces valid JSON from migrate:status parser', function () {
    $parser = new MigrateStatusParser();
    $result = $parser->parse($this->app);

    $json = json_encode($result, JSON_THROW_ON_ERROR);
    $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

    expect($decoded)->toBe($result);
    expect($decoded)->toHaveKeys(['total', 'ran', 'pending', 'migrations']);
});

it('produces valid JSON from route:list parser', function () {
    \Illuminate\Support\Facades\Route::get('/integration-test', fn () => 'ok');

    $parser = new RouteListParser();
    $result = $parser->parse($this->app);

    $json = json_encode($result, JSON_THROW_ON_ERROR);
    $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

    expect($decoded)->toBe($result);
    expect($decoded)->toHaveKeys(['total', 'routes']);
    expect($decoded['total'])->toBeGreaterThanOrEqual(1);
});
