<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\Parsers\ConfigShowParser;

it('returns config values as structured data', function () {
    $parser = new ConfigShowParser();
    $result = $parser->parseConfig($this->app, 'app');

    expect($result)->toHaveKeys(['key', 'values']);
    expect($result['key'])->toBe('app');
    expect($result['values'])->toBeArray();
    expect($result['values'])->toHaveKey('name');
});

it('returns nested key value', function () {
    $parser = new ConfigShowParser();
    $result = $parser->parseConfig($this->app, 'app.name');

    expect($result['key'])->toBe('app.name');
});
