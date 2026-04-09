<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\OutputCleaner;

it('strips ANSI escape codes', function () {
    $input = "\e[32mSuccess\e[0m \e[1;31mError\e[0m";
    expect(OutputCleaner::clean($input))->toBe('Success Error');
});

it('strips control characters except newline and carriage return', function () {
    $input = "Hello\x07World\x08Test";
    expect(OutputCleaner::clean($input))->toBe('HelloWorldTest');
});

it('preserves newlines', function () {
    $input = "Line 1\nLine 2";
    expect(OutputCleaner::clean($input))->toBe("Line 1\nLine 2");
});

it('strips unicode replacement character', function () {
    $input = "Hello\u{FFFD}World";
    expect(OutputCleaner::clean($input))->toBe('HelloWorld');
});

it('strips box-drawing characters', function () {
    $input = '┌──────┐│ test │└──────┘';
    expect(OutputCleaner::clean($input))->toBe(' test ');
});

it('strips decorative unicode symbols', function () {
    $input = '✔ Passed ✖ Failed ⚠ Warning';
    expect(OutputCleaner::clean($input))->toBe(' Passed Failed Warning');
});

it('collapses three or more dots to two', function () {
    $input = 'Loading..... done';
    expect(OutputCleaner::clean($input))->toBe('Loading.. done');
});

it('collapses multiple spaces and tabs', function () {
    $input = "Hello   \t  World";
    expect(OutputCleaner::clean($input))->toBe('Hello World');
});

it('collapses multiple blank lines', function () {
    $input = "Line 1\n\n\n\nLine 2";
    expect(OutputCleaner::clean($input))->toBe("Line 1\nLine 2");
});

it('handles combined dirty input', function () {
    $input = "\e[32m┌──┐\e[0m\n\e[32m│\e[0m Hello   World \e[32m│\e[0m\n\e[32m└──┘\e[0m";
    $result = OutputCleaner::clean($input);
    expect($result)->not->toContain("\e[");
    expect($result)->not->toContain('┌');
    expect($result)->not->toContain('│');
    expect($result)->toContain('Hello World');
});

it('returns empty string for empty input', function () {
    expect(OutputCleaner::clean(''))->toBe('');
});
