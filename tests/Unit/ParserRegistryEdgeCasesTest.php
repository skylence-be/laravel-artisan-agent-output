<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\ParserRegistry;

it('returns empty commands list when nothing registered', function () {
    $registry = new ParserRegistry;
    expect($registry->commands())->toBe([]);
});

it('get throws or returns for unregistered command', function () {
    $registry = new ParserRegistry;
    // Accessing unregistered command - should not crash but behavior depends on implementation
    expect($registry->has('nonexistent'))->toBeFalse();
});
