<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\AgentOutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

afterEach(function () {
    AgentOutputStyle::unmute();
});

it('suppresses output when muted', function () {
    $buffered = new BufferedOutput();
    $style = new AgentOutputStyle(new ArrayInput([]), $buffered);

    AgentOutputStyle::mute();
    $style->write('should not appear');
    $style->writeln('also hidden');

    expect($buffered->fetch())->toBe('');
});

it('resumes output after unmute', function () {
    $buffered = new BufferedOutput();
    $style = new AgentOutputStyle(new ArrayInput([]), $buffered);

    AgentOutputStyle::mute();
    $style->write('hidden');

    AgentOutputStyle::unmute();
    $style->write('visible');

    expect($buffered->fetch())->toBe('visible');
});

it('mute and unmute are idempotent', function () {
    $buffered = new BufferedOutput();
    $style = new AgentOutputStyle(new ArrayInput([]), $buffered);

    AgentOutputStyle::mute();
    AgentOutputStyle::mute();
    AgentOutputStyle::unmute();

    $style->write('visible');
    expect($buffered->fetch())->toBe('visible');
});

it('mute suppresses iterable messages too', function () {
    $buffered = new BufferedOutput();
    $style = new AgentOutputStyle(new ArrayInput([]), $buffered);

    AgentOutputStyle::mute();
    $style->writeln(['line1', 'line2']);

    expect($buffered->fetch())->toBe('');
});
