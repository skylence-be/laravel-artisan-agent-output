<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\Parsers\MigrateStatusParser;

beforeEach(function () {
    $this->artisan('migrate:install');
    $this->app->make('migrator')->path(__DIR__.'/../Fixtures/migrations');
});

it('returns migration status as structured data', function () {
    $parser = new MigrateStatusParser();
    $result = $parser->parse($this->app);

    expect($result)->toHaveKeys(['total', 'ran', 'pending', 'migrations']);
    expect($result['migrations'])->toBeArray();
    expect($result['total'])->toBeInt();
    expect($result['ran'])->toBeInt();
    expect($result['pending'])->toBeInt();
});

it('reports pending migrations correctly', function () {
    $parser = new MigrateStatusParser();
    $result = $parser->parse($this->app);

    expect($result['pending'])->toBe($result['total']);
    expect($result['ran'])->toBe(0);

    foreach ($result['migrations'] as $migration) {
        expect($migration)->toHaveKeys(['name', 'status', 'batch']);
        expect($migration['status'])->toBe('pending');
        expect($migration['batch'])->toBeNull();
    }
});

it('reports ran migrations after migrate', function () {
    $this->artisan('migrate', ['--no-interaction' => true]);

    $parser = new MigrateStatusParser();
    $result = $parser->parse($this->app);

    expect($result['ran'])->toBeGreaterThan(0);
    expect($result['pending'])->toBe(0);

    $ran = array_filter($result['migrations'], fn ($m) => $m['status'] === 'ran');
    expect(count($ran))->toBe($result['total']);
});
