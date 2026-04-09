<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Skylence\ArtisanAgentOutput\Parsers\RouteListParser;

it('returns routes as structured data', function () {
    Route::get('/test-route', fn () => 'ok')->name('test.route');
    Route::post('/test-post', fn () => 'ok');

    $parser = new RouteListParser();
    $result = $parser->parse($this->app);

    expect($result)->toHaveKey('routes');
    expect($result['routes'])->toBeArray();

    $testRoute = collect($result['routes'])->firstWhere('uri', 'test-route');
    expect($testRoute)->not->toBeNull();
    expect($testRoute)->toHaveKeys(['method', 'uri', 'name', 'action', 'middleware']);
    expect($testRoute['method'])->toBe('GET|HEAD');
    expect($testRoute['name'])->toBe('test.route');
});

it('returns total route count', function () {
    Route::get('/a', fn () => 'ok');
    Route::get('/b', fn () => 'ok');

    $parser = new RouteListParser();
    $result = $parser->parse($this->app);

    expect($result['total'])->toBeGreaterThanOrEqual(2);
});
