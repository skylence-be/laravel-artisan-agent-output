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

it('includes where constraints', function () {
    Route::get('/reports/{type}', fn () => 'ok')
        ->where('type', 'sales|inventory');

    $parser = new RouteListParser();
    $result = $parser->parse($this->app);

    $route = collect($result['routes'])->firstWhere('uri', 'reports/{type}');
    expect($route)->not->toBeNull();
    expect($route['wheres'])->toBe(['type' => 'sales|inventory']);
});

it('includes domain when set', function () {
    Route::domain('{account}.example.com')
        ->get('/dashboard', fn () => 'ok')
        ->name('tenant.dashboard');

    $parser = new RouteListParser();
    $result = $parser->parse($this->app);

    $route = collect($result['routes'])->firstWhere('name', 'tenant.dashboard');
    expect($route)->not->toBeNull();
    expect($route['domain'])->toBe('{account}.example.com');
});

it('excludes domain key when not set', function () {
    Route::get('/no-domain', fn () => 'ok');

    $parser = new RouteListParser();
    $result = $parser->parse($this->app);

    $route = collect($result['routes'])->firstWhere('uri', 'no-domain');
    expect($route)->not->toHaveKey('domain');
});

it('includes withoutMiddleware exclusions', function () {
    Route::middleware(['web', 'auth'])->group(function () {
        Route::get('/public', fn () => 'ok')
            ->withoutMiddleware('auth')
            ->name('public.page');
    });

    $parser = new RouteListParser();
    $result = $parser->parse($this->app);

    $route = collect($result['routes'])->firstWhere('name', 'public.page');
    expect($route)->not->toBeNull();
    expect($route['without_middleware'])->toBe(['auth']);
});
