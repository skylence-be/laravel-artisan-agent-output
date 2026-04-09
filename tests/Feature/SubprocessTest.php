<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

/**
 * Helper to run an artisan command as a subprocess with agent detection enabled.
 * Uses the btb-filament5-demo app as test target since it has the package installed.
 */
function runArtisan(string $command, bool $asAgent = true, array $extraEnv = []): Process
{
    $args = ['php', 'artisan', ...\explode(' ', $command)];

    if ($asAgent) {
        $env = array_merge(['AI_AGENT' => 'test'], $extraEnv);
    } else {
        // Setting a value to false tells Symfony Process to omit (unset) that env var
        // in the subprocess, preventing the parent Claude Code environment from leaking
        // agent detection signals into the child process.
        $env = array_merge([
            'AI_AGENT' => false,
            'CLAUDECODE' => false,
            'CLAUDE_CODE' => false,
            'CURSOR_AGENT' => false,
            'GEMINI_CLI' => false,
            'CODEX_SANDBOX' => false,
            'CODEX_THREAD_ID' => false,
            'AUGMENT_AGENT' => false,
            'OPENCODE_CLIENT' => false,
            'OPENCODE' => false,
            'AMP_CURRENT_THREAD_ID' => false,
        ], $extraEnv);
    }

    $process = new Process(
        $args,
        '/Users/xve/webdev/btb-filament5-demo',
    );

    $process->setEnv($env);
    $process->run();

    return $process;
}

it('outputs valid JSON for about command when agent detected', function (): void {
    $process = runArtisan('about');

    expect($process->isSuccessful())->toBeTrue();

    $output = trim($process->getOutput());
    $data = json_decode($output, true);

    expect($data)->not->toBeNull()
        ->and($data)->toHaveKey('environment')
        ->and($data['environment'])->toHaveKey('laravel_version');
});

it('outputs valid JSON for migrate:status when agent detected', function (): void {
    $process = runArtisan('migrate:status');

    expect($process->isSuccessful())->toBeTrue();

    $output = trim($process->getOutput());
    $data = json_decode($output, true);

    expect($data)->not->toBeNull()
        ->and($data)->toHaveKeys(['total', 'ran', 'pending', 'migrations']);
});

it('outputs valid JSON for route:list when agent detected', function (): void {
    $process = runArtisan('route:list');

    expect($process->isSuccessful())->toBeTrue();

    $output = trim($process->getOutput());
    $data = json_decode($output, true);

    expect($data)->not->toBeNull()
        ->and($data)->toHaveKeys(['total', 'routes'])
        ->and($data['total'])->toBeGreaterThan(0);
});

it('outputs valid JSON for schedule:list when agent detected', function (): void {
    $process = runArtisan('schedule:list');

    expect($process->isSuccessful())->toBeTrue();

    $output = trim($process->getOutput());
    $data = json_decode($output, true);

    expect($data)->not->toBeNull()
        ->and($data)->toHaveKeys(['total', 'tasks']);
});

it('outputs valid JSON for db:show when agent detected', function (): void {
    $process = runArtisan('db:show');

    expect($process->isSuccessful())->toBeTrue();

    $output = trim($process->getOutput());
    $data = json_decode($output, true);

    expect($data)->not->toBeNull()
        ->and($data)->toHaveKeys(['platform', 'tables']);
});

it('outputs valid JSON for event:list when agent detected', function (): void {
    $process = runArtisan('event:list');

    expect($process->isSuccessful())->toBeTrue();

    $output = trim($process->getOutput());
    $data = json_decode($output, true);

    expect($data)->not->toBeNull()
        ->and($data)->toHaveKeys(['total', 'events']);
});

it('outputs valid JSON for queue:failed when agent detected', function (): void {
    $process = runArtisan('queue:failed');

    expect($process->isSuccessful())->toBeTrue();

    $output = trim($process->getOutput());
    $data = json_decode($output, true);

    expect($data)->not->toBeNull()
        ->and($data)->toHaveKeys(['total', 'jobs']);
});

it('outputs cleaned text not JSON when ARTISAN_AGENT_OUTPUT_JSON=false', function (): void {
    $process = runArtisan('about', asAgent: true, extraEnv: [
        'ARTISAN_AGENT_OUTPUT_JSON' => 'false',
    ]);

    expect($process->isSuccessful())->toBeTrue();

    $output = trim($process->getOutput());

    // Should NOT be valid JSON
    $data = json_decode($output, true);
    expect($data)->toBeNull();

    // Should contain cleaned text
    expect($output)->toContain('Laravel');
    // Should NOT contain ANSI codes
    expect($output)->not->toContain("\e[");
    // Should NOT contain box-drawing
    expect($output)->not->toContain('┌');
    expect($output)->not->toContain('│');
});

it('outputs normal decorated text when no agent detected', function (): void {
    $process = runArtisan('about', asAgent: false);

    expect($process->isSuccessful())->toBeTrue();

    $output = trim($process->getOutput());

    // Should NOT be JSON (normal artisan output)
    $data = json_decode($output, true);
    expect($data)->toBeNull();

    // Should contain the app name somewhere
    expect($output)->toContain('Laravel');
});

it('outputs nothing when AGENT_OUTPUT_DISABLE is set', function (): void {
    $process = runArtisan('about', asAgent: true, extraEnv: [
        'AGENT_OUTPUT_DISABLE' => '1',
    ]);

    expect($process->isSuccessful())->toBeTrue();

    $output = trim($process->getOutput());

    // Should NOT be JSON — package is disabled, normal output
    $data = json_decode($output, true);
    expect($data)->toBeNull();

    // Should still have output (normal artisan)
    expect($output)->toContain('Laravel');
});

it('JSON output contains no ANSI escape codes', function (): void {
    $process = runArtisan('about');

    $output = $process->getOutput();
    expect($output)->not->toMatch('/\e\[[0-9;]*[A-Za-z]/');
});

it('JSON output contains no box-drawing characters', function (): void {
    $process = runArtisan('route:list');

    $output = $process->getOutput();
    expect($output)->not->toMatch('/[─━│┌┐└┘├┤┬┴┼═║╔╗╚╝╠╣╦╩╬]/u');
});
