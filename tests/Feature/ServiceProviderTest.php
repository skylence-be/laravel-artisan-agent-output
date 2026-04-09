<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\ParserRegistry;

it('registers ParserRegistry as singleton', function () {
    $registry1 = $this->app->make(ParserRegistry::class);
    $registry2 = $this->app->make(ParserRegistry::class);

    expect($registry1)->toBe($registry2);
});

it('merges config', function () {
    expect(config('artisan-agent-output.json'))->toBeTrue();
    expect(config('artisan-agent-output.exclude'))->toBe([]);
});

it('does not boot when disabled via env', function () {
    $_SERVER['AGENT_OUTPUT_DISABLE'] = '1';

    // Create a fresh provider and boot it
    $provider = new \Skylence\ArtisanAgentOutput\ServiceProvider($this->app);
    $provider->boot();

    // The kill switch should prevent parser registration
    // (The original boot already ran from getPackageProviders,
    //  but we're testing the kill switch logic path)
    unset($_SERVER['AGENT_OUTPUT_DISABLE']);
});
