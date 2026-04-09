<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\OutputCleaner;

it('preserves single dots in filenames and versions', function () {
    $input = 'file.php version 1.0.3';
    expect(OutputCleaner::clean($input))->toBe('file.php version 1.0.3');
});

it('handles very long strings', function () {
    $input = str_repeat("Hello \e[32mWorld\e[0m ", 1000);
    $result = OutputCleaner::clean($input);
    expect($result)->not->toContain("\e[");
    expect(strlen($result))->toBeLessThan(strlen($input));
});

it('handles string with only special characters', function () {
    $input = "\e[32m\e[0m┌──┘✔✖⚠";
    $result = OutputCleaner::clean($input);
    expect(trim($result))->toBe('');
});

it('handles mixed newlines and blank lines', function () {
    $input = "line1\n\n\n\nline2\n\nline3";
    expect(OutputCleaner::clean($input))->toBe("line1\nline2\nline3");
});

it('strips dot separators between label and value', function () {
    $input = 'Laravel Version ······················ 13.2.0';
    $result = OutputCleaner::clean($input);
    expect($result)->not->toContain('..');
    expect($result)->toContain('Laravel Version');
    expect($result)->toContain('13.2.0');
});

it('handles unicode in content', function () {
    $input = "Ünïcödé Tëst Nàme";
    expect(OutputCleaner::clean($input))->toBe("Ünïcödé Tëst Nàme");
});

it('strips ANSI with complex parameters', function () {
    $input = "\e[38;5;196mRed\e[0m \e[48;2;0;255;0mGreen BG\e[0m";
    expect(OutputCleaner::clean($input))->toBe('Red Green BG');
});
