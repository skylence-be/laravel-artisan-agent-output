<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\Contracts\CommandParser;
use Skylence\ArtisanAgentOutput\ParserRegistry;

it('registers and retrieves a parser', function () {
    $registry = new ParserRegistry;
    $registry->register('about', FakeParser::class);

    expect($registry->has('about'))->toBeTrue();
    expect($registry->get('about'))->toBe(FakeParser::class);
});

it('returns false for unregistered command', function () {
    $registry = new ParserRegistry;

    expect($registry->has('about'))->toBeFalse();
});

it('overwrites parser for same command', function () {
    $registry = new ParserRegistry;
    $registry->register('about', FakeParser::class);
    $registry->register('about', AnotherFakeParser::class);

    expect($registry->get('about'))->toBe(AnotherFakeParser::class);
});

it('returns all registered commands', function () {
    $registry = new ParserRegistry;
    $registry->register('about', FakeParser::class);
    $registry->register('route:list', AnotherFakeParser::class);

    expect($registry->commands())->toBe(['about', 'route:list']);
});

class FakeParser implements CommandParser
{
    public function parse(Illuminate\Contracts\Foundation\Application $app): array
    {
        return ['test' => true];
    }
}

class AnotherFakeParser implements CommandParser
{
    public function parse(Illuminate\Contracts\Foundation\Application $app): array
    {
        return ['other' => true];
    }
}
