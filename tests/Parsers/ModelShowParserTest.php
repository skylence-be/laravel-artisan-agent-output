<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\Parsers\ModelShowParser;

beforeEach(function () {
    $this->loadMigrationsFrom(__DIR__.'/../Fixtures/migrations');
    $this->artisan('migrate', ['--no-interaction' => true]);
});

it('returns model info as structured data', function () {
    $parser = new ModelShowParser();
    $result = $parser->parseModel($this->app, 'Tests\\Fixtures\\Models\\TestItem');

    expect($result)->toHaveKeys(['class', 'table', 'attributes', 'relations']);
    expect($result['class'])->toBe('Tests\\Fixtures\\Models\\TestItem');
    expect($result['table'])->toBe('test_items');
    expect($result['attributes'])->toBeArray();
});
