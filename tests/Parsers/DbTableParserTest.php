<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Skylence\ArtisanAgentOutput\Parsers\DbTableParser;

beforeEach(function () {
    Schema::create('parser_test_table', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->timestamps();
        $table->index('name');
    });
});

afterEach(function () {
    Schema::dropIfExists('parser_test_table');
});

it('returns table schema as structured data', function () {
    $parser = new DbTableParser;
    $result = $parser->parseTable($this->app, 'parser_test_table');

    expect($result)->toHaveKeys(['table', 'columns', 'indexes']);
    expect($result['table'])->toBe('parser_test_table');
    expect($result['columns'])->toBeArray();
    expect(count($result['columns']))->toBeGreaterThanOrEqual(4);
    expect($result['indexes'])->toBeArray();
});

it('includes column details', function () {
    $parser = new DbTableParser;
    $result = $parser->parseTable($this->app, 'parser_test_table');

    $nameCol = collect($result['columns'])->firstWhere('name', 'name');
    expect($nameCol)->not->toBeNull();
    expect($nameCol)->toHaveKeys(['name', 'type', 'nullable', 'default']);
});
