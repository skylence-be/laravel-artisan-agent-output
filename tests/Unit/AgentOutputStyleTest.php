<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\AgentOutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

it('cleans string messages on write', function () {
    $buffered = new BufferedOutput();
    $style = new AgentOutputStyle(new ArrayInput([]), $buffered);

    $style->write("\e[32mHello\e[0m");

    expect($buffered->fetch())->toBe('Hello');
});

it('cleans string messages on writeln', function () {
    $buffered = new BufferedOutput();
    $style = new AgentOutputStyle(new ArrayInput([]), $buffered);

    $style->writeln("\e[32m┌──┐\e[0m");

    $output = $buffered->fetch();
    expect($output)->not->toContain("\e[");
    expect($output)->not->toContain('┌');
});

it('cleans iterable messages', function () {
    $buffered = new BufferedOutput();
    $style = new AgentOutputStyle(new ArrayInput([]), $buffered);

    $style->writeln(["\e[32mLine 1\e[0m", "\e[31mLine 2\e[0m"]);

    $output = $buffered->fetch();
    expect($output)->toContain('Line 1');
    expect($output)->toContain('Line 2');
    expect($output)->not->toContain("\e[");
});

it('disables decoration on the output', function () {
    $buffered = new BufferedOutput();
    $buffered->setDecorated(true);

    $style = new AgentOutputStyle(new ArrayInput([]), $buffered);

    expect($buffered->isDecorated())->toBeFalse();
});
