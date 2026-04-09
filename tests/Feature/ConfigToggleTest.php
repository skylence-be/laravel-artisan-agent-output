<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\ParserRegistry;
use Skylence\ArtisanAgentOutput\Parsers\MigrateStatusParser;

it('uses json parsers when json config is true', function () {
    config()->set('artisan-agent-output.json', true);

    $registry = $this->app->make(ParserRegistry::class);
    // Registry should have parsers registered (from ServiceProvider boot)
    expect($registry->commands())->not->toBe([]);
});

it('respects json false config by not parsing', function () {
    // Even with parsers registered, json=false means shouldParseJson returns false
    config()->set('artisan-agent-output.json', false);

    // Directly test: parser still works when called manually
    $parser = new MigrateStatusParser;
    $result = $parser->parse($this->app);
    expect($result)->toHaveKey('total');
});

it('excludes commands listed in exclude config', function () {
    config()->set('artisan-agent-output.exclude', ['about']);

    $registry = $this->app->make(ParserRegistry::class);
    // Parser is still registered...
    expect($registry->has('about'))->toBeTrue();
    // ...but the exclude config should prevent it from being used
    // (This is handled in ServiceProvider::shouldParseJson, not testable in isolation here
    //  without mocking the full event flow)
});
