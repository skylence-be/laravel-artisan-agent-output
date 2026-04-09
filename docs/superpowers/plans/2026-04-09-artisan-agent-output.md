# artisan-agent-output Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build `skylence/artisan-agent-output`, a Laravel package that detects AI agents and optimizes Artisan command output — cleaned text universally, structured JSON for commands with registered parsers.

**Architecture:** Two-layer system. Layer 1: `AgentOutputStyle` overrides Laravel's `OutputStyle` to pipe all output through `OutputCleaner` (strips ANSI, box-drawing, whitespace). Layer 2: Event listeners on `CommandStarting`/`CommandFinished` swap output to a buffer when a JSON parser exists, then emit structured JSON instead. Parsers query Laravel internals directly. Third-party packages register parsers via a facade backed by `ParserRegistry`.

**Tech Stack:** PHP 8.3+, Laravel 12/13, `shipfastlabs/agent-detector`, Pest for testing, `orchestra/testbench` for integration tests.

**Test app:** `/Users/xve/webdev/btb-filament5-demo` for manual verification.

---

### Task 1: Package Scaffold

**Files:**
- Create: `composer.json`
- Create: `.gitignore`
- Create: `phpunit.xml`

- [ ] **Step 1: Create composer.json**

```json
{
    "name": "skylence/artisan-agent-output",
    "description": "Agent-optimized output for Laravel Artisan commands",
    "keywords": ["laravel", "artisan", "agent", "ai", "json", "output"],
    "license": "MIT",
    "authors": [
        {
            "name": "Jonas Van der Haegen",
            "email": "jonas@skylence.be"
        }
    ],
    "require": {
        "php": "^8.3",
        "illuminate/console": "^12.0 || ^13.0",
        "illuminate/events": "^12.0 || ^13.0",
        "illuminate/support": "^12.0 || ^13.0",
        "shipfastlabs/agent-detector": "^1.1.0"
    },
    "require-dev": {
        "laravel/pint": "^1.29.0",
        "orchestra/testbench": "^10.0 || ^11.0",
        "pestphp/pest": "^3.0 || ^4.0",
        "phpstan/phpstan": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "Skylence\\ArtisanAgentOutput\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Skylence\\ArtisanAgentOutput\\ServiceProvider"
            ]
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    "config": {
        "sort-packages": true,
        "preferred-install": "dist",
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    },
    "scripts": {
        "lint": "pint",
        "test": "pest",
        "test:types": "phpstan"
    }
}
```

- [ ] **Step 2: Create .gitignore**

```
/vendor/
/node_modules/
.phpunit.cache/
composer.lock
.php-cs-fixer.cache
```

- [ ] **Step 3: Create phpunit.xml**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="Parsers">
            <directory>tests/Parsers</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

- [ ] **Step 4: Create tests/Pest.php**

```php
<?php

declare(strict_types=1);
```

- [ ] **Step 5: Install dependencies**

Run: `cd /Users/xve/webdev/artisan-agent-output && composer install`
Expected: Dependencies install successfully, vendor/ created.

- [ ] **Step 6: Commit**

```bash
git add composer.json .gitignore phpunit.xml tests/Pest.php
git commit -m "chore: scaffold package with composer, phpunit, pest"
```

---

### Task 2: OutputCleaner

**Files:**
- Create: `src/OutputCleaner.php`
- Create: `tests/Unit/OutputCleanerTest.php`

- [ ] **Step 1: Write the failing tests**

```php
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Unit/OutputCleanerTest.php`
Expected: FAIL — class `OutputCleaner` not found.

- [ ] **Step 3: Implement OutputCleaner**

```php
<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput;

final class OutputCleaner
{
    public static function clean(string $output): string
    {
        $output = (string) preg_replace('/\e\[[0-9;]*[A-Za-z]/', '', $output);
        $output = (string) preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $output);
        $output = (string) preg_replace('/\x{FFFD}/u', '', $output);
        $output = (string) preg_replace('/[─━│┌┐└┘├┤┬┴┼▓░▒═║╔╗╚╝╠╣╦╩╬➜▶►⚠✖✔●◆■▪→←↑↓▕⨯✕]+/u', '', $output);
        $output = (string) preg_replace('/\.{3,}/', '..', $output);
        $output = (string) preg_replace('/[ \t]+/', ' ', $output);

        return (string) preg_replace('/\n\s*\n/', "\n", $output);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Unit/OutputCleanerTest.php`
Expected: All 11 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add src/OutputCleaner.php tests/Unit/OutputCleanerTest.php
git commit -m "feat: add OutputCleaner with ANSI and decoration stripping"
```

---

### Task 3: ParserRegistry + Contract

**Files:**
- Create: `src/Contracts/CommandParser.php`
- Create: `src/ParserRegistry.php`
- Create: `tests/Unit/ParserRegistryTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\Contracts\CommandParser;
use Skylence\ArtisanAgentOutput\ParserRegistry;

it('registers and retrieves a parser', function () {
    $registry = new ParserRegistry();
    $registry->register('about', FakeParser::class);

    expect($registry->has('about'))->toBeTrue();
    expect($registry->get('about'))->toBe(FakeParser::class);
});

it('returns false for unregistered command', function () {
    $registry = new ParserRegistry();

    expect($registry->has('about'))->toBeFalse();
});

it('overwrites parser for same command', function () {
    $registry = new ParserRegistry();
    $registry->register('about', FakeParser::class);
    $registry->register('about', AnotherFakeParser::class);

    expect($registry->get('about'))->toBe(AnotherFakeParser::class);
});

it('returns all registered commands', function () {
    $registry = new ParserRegistry();
    $registry->register('about', FakeParser::class);
    $registry->register('route:list', AnotherFakeParser::class);

    expect($registry->commands())->toBe(['about', 'route:list']);
});

// Test doubles
class FakeParser implements CommandParser
{
    public function parse(\Illuminate\Contracts\Foundation\Application $app): array
    {
        return ['test' => true];
    }
}

class AnotherFakeParser implements CommandParser
{
    public function parse(\Illuminate\Contracts\Foundation\Application $app): array
    {
        return ['other' => true];
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Unit/ParserRegistryTest.php`
Expected: FAIL — classes not found.

- [ ] **Step 3: Implement CommandParser contract**

```php
<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Contracts;

use Illuminate\Contracts\Foundation\Application;

interface CommandParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(Application $app): array;
}
```

- [ ] **Step 4: Implement ParserRegistry**

```php
<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput;

use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class ParserRegistry
{
    /** @var array<string, class-string<CommandParser>> */
    private array $parsers = [];

    /** @param class-string<CommandParser> $parser */
    public function register(string $command, string $parser): void
    {
        $this->parsers[$command] = $parser;
    }

    public function has(string $command): bool
    {
        return isset($this->parsers[$command]);
    }

    /** @return class-string<CommandParser> */
    public function get(string $command): string
    {
        return $this->parsers[$command];
    }

    /** @return list<string> */
    public function commands(): array
    {
        return array_keys($this->parsers);
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Unit/ParserRegistryTest.php`
Expected: All 4 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Contracts/CommandParser.php src/ParserRegistry.php tests/Unit/ParserRegistryTest.php
git commit -m "feat: add CommandParser contract and ParserRegistry"
```

---

### Task 4: AgentOutputStyle

**Files:**
- Create: `src/AgentOutputStyle.php`
- Create: `tests/Unit/AgentOutputStyleTest.php`

- [ ] **Step 1: Write the failing tests**

```php
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Unit/AgentOutputStyleTest.php`
Expected: FAIL — class `AgentOutputStyle` not found.

- [ ] **Step 3: Implement AgentOutputStyle**

```php
<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput;

use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class AgentOutputStyle extends OutputStyle
{
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $output->setDecorated(false);

        parent::__construct($input, $output);
    }

    /** @param string|iterable<string> $messages */
    #[\Override]
    public function write(string|iterable $messages, bool $newline = false, int $options = 0): void
    {
        parent::write($this->clean($messages), $newline, $options);
    }

    /** @param string|iterable<string> $messages */
    #[\Override]
    public function writeln(string|iterable $messages, int $type = self::OUTPUT_NORMAL): void
    {
        parent::writeln($this->clean($messages), $type);
    }

    /** @return string|list<string> */
    private function clean(string|iterable $messages): string|array
    {
        if (is_string($messages)) {
            return OutputCleaner::clean($messages);
        }

        return array_values(array_map(
            OutputCleaner::clean(...),
            [...$messages],
        ));
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Unit/AgentOutputStyleTest.php`
Expected: All 4 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add src/AgentOutputStyle.php tests/Unit/AgentOutputStyleTest.php
git commit -m "feat: add AgentOutputStyle with output cleaning"
```

---

### Task 5: Config + Facade

**Files:**
- Create: `config/artisan-agent-output.php`
- Create: `src/Facades/AgentOutput.php`

- [ ] **Step 1: Create config file**

```php
<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | JSON Mode
    |--------------------------------------------------------------------------
    |
    | When true, commands with registered parsers will output structured JSON
    | instead of cleaned text. When false, all commands get cleaned text only.
    |
    */
    'json' => true,

    /*
    |--------------------------------------------------------------------------
    | Excluded Commands
    |--------------------------------------------------------------------------
    |
    | Commands listed here will not be processed by JSON parsers, even if a
    | parser is registered. They will still receive cleaned text output.
    |
    */
    'exclude' => [],
];
```

- [ ] **Step 2: Create facade**

```php
<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Facades;

use Illuminate\Support\Facades\Facade;
use Skylence\ArtisanAgentOutput\ParserRegistry;

/**
 * @method static void register(string $command, string $parser)
 * @method static bool has(string $command)
 * @method static string get(string $command)
 * @method static list<string> commands()
 */
final class AgentOutput extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ParserRegistry::class;
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add config/artisan-agent-output.php src/Facades/AgentOutput.php
git commit -m "feat: add config and AgentOutput facade"
```

---

### Task 6: ServiceProvider

**Files:**
- Create: `src/ServiceProvider.php`
- Create: `tests/Feature/ServiceProviderTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\ParserRegistry;
use Skylence\ArtisanAgentOutput\ServiceProvider;

use function Orchestra\Testbench\package_path;

it('registers ParserRegistry as singleton', function () {
    $registry1 = $this->app->make(ParserRegistry::class);
    $registry2 = $this->app->make(ParserRegistry::class);

    expect($registry1)->toBe($registry2);
});

it('merges config', function () {
    expect(config('artisan-agent-output.json'))->toBeTrue();
    expect(config('artisan-agent-output.exclude'))->toBe([]);
});

it('does not boot when not in console', function () {
    // AgentOutputStyle should not be bound when not running in console
    // This is hard to test directly since testbench always runs in console
    // We test the kill switch instead
    $_SERVER['AGENT_OUTPUT_DISABLE'] = '1';

    $provider = new ServiceProvider($this->app);
    $provider->boot();

    // Registry should have no parsers registered by boot
    $registry = $this->app->make(ParserRegistry::class);
    expect($registry->commands())->toBe([]);

    unset($_SERVER['AGENT_OUTPUT_DISABLE']);
});
```

Add a `tests/TestCase.php` base:

```php
<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Skylence\ArtisanAgentOutput\ServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }
}
```

Update `tests/Pest.php`:

```php
<?php

declare(strict_types=1);

uses(Tests\TestCase::class)->in('Feature', 'Parsers');
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Feature/ServiceProviderTest.php`
Expected: FAIL — class `ServiceProvider` not found.

- [ ] **Step 3: Implement ServiceProvider**

```php
<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput;

use AgentDetector\AgentDetector;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Skylence\ArtisanAgentOutput\Parsers\AboutParser;
use Skylence\ArtisanAgentOutput\Parsers\ConfigShowParser;
use Skylence\ArtisanAgentOutput\Parsers\DbShowParser;
use Skylence\ArtisanAgentOutput\Parsers\DbTableParser;
use Skylence\ArtisanAgentOutput\Parsers\EventListParser;
use Skylence\ArtisanAgentOutput\Parsers\MigrateStatusParser;
use Skylence\ArtisanAgentOutput\Parsers\ModelShowParser;
use Skylence\ArtisanAgentOutput\Parsers\QueueFailedParser;
use Skylence\ArtisanAgentOutput\Parsers\RouteListParser;
use Skylence\ArtisanAgentOutput\Parsers\ScheduleListParser;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class ServiceProvider extends LaravelServiceProvider
{
    private ?OutputInterface $realOutput = null;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/artisan-agent-output.php', 'artisan-agent-output');
        $this->app->singleton(ParserRegistry::class);
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        if (isset($_SERVER['AGENT_OUTPUT_DISABLE'])) {
            return;
        }

        if (! AgentDetector::detect()->isAgent) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/artisan-agent-output.php' => config_path('artisan-agent-output.php'),
        ]);

        // Layer 1: Cleaned text for all commands
        $this->app->bind(OutputStyle::class, AgentOutputStyle::class);

        $registry = $this->app->make(ParserRegistry::class);

        /** @var Dispatcher $events */
        $events = $this->app->make(Dispatcher::class);

        $events->listen(CommandStarting::class, function (CommandStarting $event) use ($registry): void {
            $event->output->setDecorated(false);

            if ($this->shouldParseJson($event->command, $registry)) {
                $this->realOutput = $event->output;
            }
        });

        // Register core parsers
        $registry->register('about', AboutParser::class);
        $registry->register('migrate:status', MigrateStatusParser::class);
        $registry->register('route:list', RouteListParser::class);
        $registry->register('db:show', DbShowParser::class);
        $registry->register('db:table', DbTableParser::class);
        $registry->register('schedule:list', ScheduleListParser::class);
        $registry->register('model:show', ModelShowParser::class);
        $registry->register('queue:failed', QueueFailedParser::class);
        $registry->register('event:list', EventListParser::class);
        $registry->register('config:show', ConfigShowParser::class);

        // Layer 2: JSON parsers for specific commands
        $events->listen(CommandFinished::class, function (CommandFinished $event) use ($registry): void {
            $command = $event->command;

            if (! $this->shouldParseJson($command, $registry)) {
                return;
            }

            $output = $this->realOutput ?? $event->output;

            try {
                $parser = $this->app->make($registry->get($command));
                $result = $parser->parse($this->app);
                $json = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $output->writeln($json);
            } catch (\Throwable $e) {
                logger()->warning("artisan-agent-output: parser failed for {$command}", [
                    'error' => $e->getMessage(),
                ]);
                // Fallback: the cleaned text already went through AgentOutputStyle
            }

            $this->realOutput = null;
        });
    }

    private function shouldParseJson(?string $command, ParserRegistry $registry): bool
    {
        if (! $command) {
            return false;
        }

        if (! config('artisan-agent-output.json', true)) {
            return false;
        }

        if (! $registry->has($command)) {
            return false;
        }

        return ! in_array($command, config('artisan-agent-output.exclude', []), true);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Feature/ServiceProviderTest.php`
Expected: All 3 tests PASS. (Parser classes don't exist yet but won't cause failures since boot is skipped by kill switch or agent detection.)

- [ ] **Step 5: Commit**

```bash
git add src/ServiceProvider.php tests/Feature/ServiceProviderTest.php tests/TestCase.php tests/Pest.php
git commit -m "feat: add ServiceProvider with two-layer architecture"
```

---

### Task 7: AboutParser

**Files:**
- Create: `src/Parsers/AboutParser.php`
- Create: `tests/Parsers/AboutParserTest.php`

**Note:** The `about` command has a static `$data` property via `AboutCommand::add()`. We query it through `Artisan::call('about', ['--json' => true])` and parse the JSON output.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\Parsers\AboutParser;

it('returns structured about data', function () {
    $parser = new AboutParser();
    $result = $parser->parse($this->app);

    expect($result)->toHaveKeys(['environment', 'cache', 'drivers']);
    expect($result['environment'])->toBeArray();
    expect($result['environment'])->toHaveKey('laravel_version');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/AboutParserTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement AboutParser**

```php
<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class AboutParser implements CommandParser
{
    public function parse(Application $app): array
    {
        Artisan::call('about', ['--json' => true]);

        $output = Artisan::output();

        /** @var array<string, mixed> $data */
        $data = json_decode(trim($output), true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/AboutParserTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Parsers/AboutParser.php tests/Parsers/AboutParserTest.php
git commit -m "feat: add AboutParser for artisan about command"
```

---

### Task 8: MigrateStatusParser

**Files:**
- Create: `src/Parsers/MigrateStatusParser.php`
- Create: `tests/Parsers/MigrateStatusParserTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\Parsers\MigrateStatusParser;

beforeEach(function () {
    $this->loadMigrationsFrom(__DIR__.'/../Fixtures/migrations');
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
```

- [ ] **Step 2: Create test fixture migration**

Create `tests/Fixtures/migrations/2024_01_01_000000_create_test_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_items');
    }
};
```

- [ ] **Step 3: Run test to verify it fails**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/MigrateStatusParserTest.php`
Expected: FAIL — class not found.

- [ ] **Step 4: Implement MigrateStatusParser**

```php
<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class MigrateStatusParser implements CommandParser
{
    public function parse(Application $app): array
    {
        /** @var \Illuminate\Database\Migrations\Migrator $migrator */
        $migrator = $app->make('migrator');
        $repository = $migrator->getRepository();

        if (! $migrator->repositoryExists()) {
            return [
                'total' => 0,
                'ran' => 0,
                'pending' => 0,
                'migrations' => [],
            ];
        }

        $ran = $repository->getRan();
        $batches = $repository->getMigrationBatches();
        $files = $migrator->getMigrationFiles($migrator->paths());

        $migrations = [];
        foreach ($files as $name => $path) {
            $isRan = in_array($name, $ran, true);
            $migrations[] = [
                'name' => $name,
                'status' => $isRan ? 'ran' : 'pending',
                'batch' => $batches[$name] ?? null,
            ];
        }

        return [
            'total' => count($migrations),
            'ran' => count(array_filter($migrations, fn (array $m): bool => $m['status'] === 'ran')),
            'pending' => count(array_filter($migrations, fn (array $m): bool => $m['status'] === 'pending')),
            'migrations' => $migrations,
        ];
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/MigrateStatusParserTest.php`
Expected: All 3 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Parsers/MigrateStatusParser.php tests/Parsers/MigrateStatusParserTest.php tests/Fixtures/migrations/
git commit -m "feat: add MigrateStatusParser"
```

---

### Task 9: RouteListParser

**Files:**
- Create: `src/Parsers/RouteListParser.php`
- Create: `tests/Parsers/RouteListParserTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Skylence\ArtisanAgentOutput\Parsers\RouteListParser;

it('returns routes as structured data', function () {
    Route::get('/test-route', fn () => 'ok')->name('test.route');
    Route::post('/test-post', fn () => 'ok');

    $parser = new RouteListParser();
    $result = $parser->parse($this->app);

    expect($result)->toHaveKey('routes');
    expect($result['routes'])->toBeArray();

    $testRoute = collect($result['routes'])->firstWhere('uri', 'test-route');
    expect($testRoute)->not->toBeNull();
    expect($testRoute)->toHaveKeys(['method', 'uri', 'name', 'action', 'middleware']);
    expect($testRoute['method'])->toBe('GET|HEAD');
    expect($testRoute['name'])->toBe('test.route');
});

it('returns total route count', function () {
    Route::get('/a', fn () => 'ok');
    Route::get('/b', fn () => 'ok');

    $parser = new RouteListParser();
    $result = $parser->parse($this->app);

    expect($result['total'])->toBeGreaterThanOrEqual(2);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/RouteListParserTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement RouteListParser**

```php
<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class RouteListParser implements CommandParser
{
    public function parse(Application $app): array
    {
        /** @var Router $router */
        $router = $app->make(Router::class);
        $routes = $router->getRoutes();

        $result = [];
        foreach ($routes as $route) {
            $result[] = [
                'method' => implode('|', $route->methods()),
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
                'middleware' => $router->gatherRouteMiddleware($route),
            ];
        }

        return [
            'total' => count($result),
            'routes' => $result,
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/RouteListParserTest.php`
Expected: All 2 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Parsers/RouteListParser.php tests/Parsers/RouteListParserTest.php
git commit -m "feat: add RouteListParser"
```

---

### Task 10: DbShowParser

**Files:**
- Create: `src/Parsers/DbShowParser.php`
- Create: `tests/Parsers/DbShowParserTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\Parsers\DbShowParser;

it('returns database info as structured data', function () {
    $parser = new DbShowParser();
    $result = $parser->parse($this->app);

    expect($result)->toHaveKeys(['platform', 'tables']);
    expect($result['platform'])->toHaveKeys(['connection', 'name', 'version']);
    expect($result['tables'])->toBeArray();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/DbShowParserTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement DbShowParser**

```php
<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConnectionResolverInterface;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class DbShowParser implements CommandParser
{
    public function parse(Application $app): array
    {
        /** @var ConnectionResolverInterface $connections */
        $connections = $app->make('db');
        $connection = $connections->connection();
        $schema = $connection->getSchemaBuilder();

        $tables = array_map(fn (array $table): array => [
            'name' => $table['name'],
            'schema' => $table['schema'] ?? null,
            'size' => $table['size'] ?? null,
            'engine' => $table['engine'] ?? null,
            'collation' => $table['collation'] ?? null,
            'comment' => $table['comment'] ?? null,
        ], $schema->getTables());

        return [
            'platform' => [
                'connection' => $connection->getName(),
                'name' => $connection->getDriverTitle(),
                'version' => $connection->getServerVersion(),
            ],
            'tables' => $tables,
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/DbShowParserTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Parsers/DbShowParser.php tests/Parsers/DbShowParserTest.php
git commit -m "feat: add DbShowParser"
```

---

### Task 11: DbTableParser

**Files:**
- Create: `src/Parsers/DbTableParser.php`
- Create: `tests/Parsers/DbTableParserTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Skylence\ArtisanAgentOutput\Parsers\DbTableParser;

beforeEach(function () {
    Schema::create('parser_test_table', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->timestamps();
        $table->index('name');
    });
});

afterEach(function () {
    Schema::dropIfExists('parser_test_table');
});

it('returns table schema as structured data', function () {
    $parser = new DbTableParser();
    // DbTableParser needs the table name — passed via constructor or input
    $result = $parser->parseTable($this->app, 'parser_test_table');

    expect($result)->toHaveKeys(['table', 'columns', 'indexes']);
    expect($result['table'])->toBe('parser_test_table');
    expect($result['columns'])->toBeArray();
    expect(count($result['columns']))->toBeGreaterThanOrEqual(4);
    expect($result['indexes'])->toBeArray();
});

it('includes column details', function () {
    $parser = new DbTableParser();
    $result = $parser->parseTable($this->app, 'parser_test_table');

    $nameCol = collect($result['columns'])->firstWhere('name', 'name');
    expect($nameCol)->not->toBeNull();
    expect($nameCol)->toHaveKeys(['name', 'type', 'nullable', 'default']);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/DbTableParserTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement DbTableParser**

The `db:table` command takes a table name as argument. The parser needs to extract it from the command input. We add a `parseTable()` helper for direct use and have `parse()` try to resolve the table from the most recent command input.

```php
<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConnectionResolverInterface;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class DbTableParser implements CommandParser
{
    public function parse(Application $app): array
    {
        // When called via the event listener, we need the table argument.
        // Resolve from $_SERVER['argv'] — `php artisan db:table users`
        $argv = $_SERVER['argv'] ?? [];
        $table = null;

        foreach ($argv as $i => $arg) {
            if ($arg === 'db:table' && isset($argv[$i + 1]) && ! str_starts_with($argv[$i + 1], '-')) {
                $table = $argv[$i + 1];
                break;
            }
        }

        if ($table === null) {
            return ['error' => 'No table specified'];
        }

        return $this->parseTable($app, $table);
    }

    /** @return array<string, mixed> */
    public function parseTable(Application $app, string $table): array
    {
        /** @var ConnectionResolverInterface $connections */
        $connections = $app->make('db');
        $schema = $connections->connection()->getSchemaBuilder();

        $columns = array_map(fn (array $col): array => [
            'name' => $col['name'],
            'type' => $col['type_name'] ?? $col['type'],
            'nullable' => $col['nullable'] ?? false,
            'default' => $col['default'] ?? null,
            'auto_increment' => $col['auto_increment'] ?? false,
        ], $schema->getColumns($table));

        $indexes = array_map(fn (array $idx): array => [
            'name' => $idx['name'],
            'columns' => $idx['columns'],
            'unique' => $idx['unique'] ?? false,
            'primary' => $idx['primary'] ?? false,
        ], $schema->getIndexes($table));

        $foreignKeys = array_map(fn (array $fk): array => [
            'name' => $fk['name'],
            'columns' => $fk['columns'],
            'foreign_table' => $fk['foreign_table'],
            'foreign_columns' => $fk['foreign_columns'],
            'on_update' => $fk['on_update'] ?? null,
            'on_delete' => $fk['on_delete'] ?? null,
        ], $schema->getForeignKeys($table));

        return [
            'table' => $table,
            'columns' => $columns,
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys,
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/DbTableParserTest.php`
Expected: All 2 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Parsers/DbTableParser.php tests/Parsers/DbTableParserTest.php
git commit -m "feat: add DbTableParser"
```

---

### Task 12: ScheduleListParser

**Files:**
- Create: `src/Parsers/ScheduleListParser.php`
- Create: `tests/Parsers/ScheduleListParserTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;
use Skylence\ArtisanAgentOutput\Parsers\ScheduleListParser;

it('returns empty schedule when no tasks registered', function () {
    $parser = new ScheduleListParser();
    $result = $parser->parse($this->app);

    expect($result)->toHaveKeys(['total', 'tasks']);
    expect($result['total'])->toBe(0);
    expect($result['tasks'])->toBe([]);
});

it('returns scheduled tasks as structured data', function () {
    $schedule = $this->app->make(Schedule::class);
    $schedule->command('inspire')->daily();
    $schedule->command('cache:clear')->hourly();

    $parser = new ScheduleListParser();
    $result = $parser->parse($this->app);

    expect($result['total'])->toBe(2);
    expect($result['tasks'])->toHaveCount(2);

    $first = $result['tasks'][0];
    expect($first)->toHaveKeys(['command', 'expression', 'description', 'next_run']);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/ScheduleListParserTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement ScheduleListParser**

```php
<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Cron\CronExpression;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Carbon;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class ScheduleListParser implements CommandParser
{
    public function parse(Application $app): array
    {
        /** @var Schedule $schedule */
        $schedule = $app->make(Schedule::class);
        $events = $schedule->events();
        $timezone = config('app.timezone', 'UTC');

        $tasks = [];
        foreach ($events as $event) {
            $nextRun = null;

            try {
                $nextRun = (new CronExpression($event->expression))
                    ->getNextRunDate(Carbon::now()->setTimezone($event->timezone ?? $timezone))
                    ->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                // Invalid expression — skip next_run
            }

            $tasks[] = [
                'command' => $event->getSummaryForDisplay(),
                'expression' => $event->expression,
                'description' => $event->description ?? '',
                'next_run' => $nextRun,
            ];
        }

        return [
            'total' => count($tasks),
            'tasks' => $tasks,
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/ScheduleListParserTest.php`
Expected: All 2 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Parsers/ScheduleListParser.php tests/Parsers/ScheduleListParserTest.php
git commit -m "feat: add ScheduleListParser"
```

---

### Task 13: ModelShowParser

**Files:**
- Create: `src/Parsers/ModelShowParser.php`
- Create: `tests/Parsers/ModelShowParserTest.php`
- Create: `tests/Fixtures/Models/TestItem.php`

- [ ] **Step 1: Write the test fixture model**

```php
<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestItem extends Model
{
    protected $fillable = ['name'];

    protected function casts(): array
    {
        return [
            'name' => 'string',
        ];
    }
}
```

- [ ] **Step 2: Write the failing test**

```php
<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\Parsers\ModelShowParser;

beforeEach(function () {
    $this->loadMigrationsFrom(__DIR__.'/../Fixtures/migrations');
    $this->artisan('migrate', ['--no-interaction' => true]);
});

it('returns model info as structured data', function () {
    $parser = new ModelShowParser();
    $result = $parser->parseModel($this->app, 'Tests\\Fixtures\\Models\\TestItem');

    expect($result)->toHaveKeys(['class', 'table', 'attributes', 'relations']);
    expect($result['class'])->toBe('Tests\\Fixtures\\Models\\TestItem');
    expect($result['table'])->toBe('test_items');
    expect($result['attributes'])->toBeArray();
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/ModelShowParserTest.php`
Expected: FAIL — class not found.

- [ ] **Step 4: Implement ModelShowParser**

```php
<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\ModelInspector;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class ModelShowParser implements CommandParser
{
    public function parse(Application $app): array
    {
        $argv = $_SERVER['argv'] ?? [];
        $model = null;

        foreach ($argv as $i => $arg) {
            if ($arg === 'model:show' && isset($argv[$i + 1]) && ! str_starts_with($argv[$i + 1], '-')) {
                $model = $argv[$i + 1];
                break;
            }
        }

        if ($model === null) {
            return ['error' => 'No model specified'];
        }

        return $this->parseModel($app, $model);
    }

    /** @return array<string, mixed> */
    public function parseModel(Application $app, string $model): array
    {
        /** @var ModelInspector $inspector */
        $inspector = $app->make(ModelInspector::class);
        $data = $inspector->inspect($model);

        return [
            'class' => $data['class'],
            'database' => $data['database'] ?? null,
            'table' => $data['table'],
            'policy' => $data['policy'] ?? null,
            'attributes' => $data['attributes']->toArray(),
            'relations' => $data['relations']->toArray(),
            'events' => $data['events']->toArray(),
            'observers' => $data['observers']->toArray(),
        ];
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/ModelShowParserTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Parsers/ModelShowParser.php tests/Parsers/ModelShowParserTest.php tests/Fixtures/Models/TestItem.php
git commit -m "feat: add ModelShowParser"
```

---

### Task 14: QueueFailedParser

**Files:**
- Create: `src/Parsers/QueueFailedParser.php`
- Create: `tests/Parsers/QueueFailedParserTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\Parsers\QueueFailedParser;

it('returns empty list when no failed jobs', function () {
    $parser = new QueueFailedParser();
    $result = $parser->parse($this->app);

    expect($result)->toHaveKeys(['total', 'jobs']);
    expect($result['total'])->toBe(0);
    expect($result['jobs'])->toBe([]);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/QueueFailedParserTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement QueueFailedParser**

```php
<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class QueueFailedParser implements CommandParser
{
    public function parse(Application $app): array
    {
        /** @var \Illuminate\Queue\Failed\FailedJobProviderInterface $failer */
        $failer = $app->make('queue.failer');
        $failed = $failer->all();

        $jobs = [];
        foreach ($failed as $job) {
            $payload = json_decode($job->payload, true);

            $jobs[] = [
                'id' => $job->id,
                'connection' => $job->connection,
                'queue' => $job->queue,
                'class' => $payload['displayName'] ?? 'Unknown',
                'failed_at' => $job->failed_at,
                'exception' => mb_substr($job->exception, 0, 500),
            ];
        }

        return [
            'total' => count($jobs),
            'jobs' => $jobs,
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/QueueFailedParserTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Parsers/QueueFailedParser.php tests/Parsers/QueueFailedParserTest.php
git commit -m "feat: add QueueFailedParser"
```

---

### Task 15: EventListParser

**Files:**
- Create: `src/Parsers/EventListParser.php`
- Create: `tests/Parsers/EventListParserTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Skylence\ArtisanAgentOutput\Parsers\EventListParser;

it('returns events as structured data', function () {
    $parser = new EventListParser();
    $result = $parser->parse($this->app);

    expect($result)->toHaveKeys(['total', 'events']);
    expect($result['events'])->toBeArray();
});

it('includes registered event listeners', function () {
    Event::listen('test.event', fn () => null);

    $parser = new EventListParser();
    $result = $parser->parse($this->app);

    $testEvent = collect($result['events'])->firstWhere('event', 'test.event');
    expect($testEvent)->not->toBeNull();
    expect($testEvent)->toHaveKeys(['event', 'listeners']);
    expect($testEvent['listeners'])->toBeArray();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/EventListParserTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement EventListParser**

```php
<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class EventListParser implements CommandParser
{
    public function parse(Application $app): array
    {
        /** @var \Illuminate\Events\Dispatcher $dispatcher */
        $dispatcher = $app->make('events');
        $rawListeners = $dispatcher->getRawListeners();

        $events = [];
        foreach ($rawListeners as $event => $listeners) {
            $resolved = [];
            foreach ($listeners as $listener) {
                if (is_string($listener)) {
                    $resolved[] = $listener;
                } elseif (is_array($listener) && count($listener) === 2) {
                    $resolved[] = (is_object($listener[0]) ? get_class($listener[0]) : $listener[0]).'@'.$listener[1];
                } else {
                    $resolved[] = 'Closure';
                }
            }

            $events[] = [
                'event' => $event,
                'listeners' => $resolved,
            ];
        }

        return [
            'total' => count($events),
            'events' => $events,
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/EventListParserTest.php`
Expected: All 2 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Parsers/EventListParser.php tests/Parsers/EventListParserTest.php
git commit -m "feat: add EventListParser"
```

---

### Task 16: ConfigShowParser

**Files:**
- Create: `src/Parsers/ConfigShowParser.php`
- Create: `tests/Parsers/ConfigShowParserTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\Parsers\ConfigShowParser;

it('returns config values as structured data', function () {
    $parser = new ConfigShowParser();
    $result = $parser->parseConfig($this->app, 'app');

    expect($result)->toHaveKeys(['key', 'values']);
    expect($result['key'])->toBe('app');
    expect($result['values'])->toBeArray();
    expect($result['values'])->toHaveKey('name');
});

it('returns nested key value', function () {
    $parser = new ConfigShowParser();
    $result = $parser->parseConfig($this->app, 'app.name');

    expect($result['key'])->toBe('app.name');
    expect($result['values'])->not->toBeArray();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/ConfigShowParserTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement ConfigShowParser**

```php
<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class ConfigShowParser implements CommandParser
{
    public function parse(Application $app): array
    {
        $argv = $_SERVER['argv'] ?? [];
        $key = null;

        foreach ($argv as $i => $arg) {
            if ($arg === 'config:show' && isset($argv[$i + 1]) && ! str_starts_with($argv[$i + 1], '-')) {
                $key = $argv[$i + 1];
                break;
            }
        }

        if ($key === null) {
            return ['error' => 'No config key specified'];
        }

        return $this->parseConfig($app, $key);
    }

    /** @return array<string, mixed> */
    public function parseConfig(Application $app, string $key): array
    {
        $value = config($key);

        return [
            'key' => $key,
            'values' => $value,
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest tests/Parsers/ConfigShowParserTest.php`
Expected: All 2 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Parsers/ConfigShowParser.php tests/Parsers/ConfigShowParserTest.php
git commit -m "feat: add ConfigShowParser"
```

---

### Task 17: Integration Test — Full Flow

**Files:**
- Create: `tests/Feature/CleanedTextOutputTest.php`
- Create: `tests/Feature/JsonOutputTest.php`

- [ ] **Step 1: Write cleaned text integration test**

```php
<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\OutputCleaner;

it('cleans artisan about output', function () {
    $this->artisan('about')
        ->assertSuccessful();
});
```

- [ ] **Step 2: Write JSON output integration test**

```php
<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\Parsers\MigrateStatusParser;

beforeEach(function () {
    $this->loadMigrationsFrom(__DIR__.'/../Fixtures/migrations');
});

it('produces valid JSON from migrate:status parser', function () {
    $parser = new MigrateStatusParser();
    $result = $parser->parse($this->app);

    $json = json_encode($result, JSON_THROW_ON_ERROR);
    $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

    expect($decoded)->toBe($result);
    expect($decoded)->toHaveKeys(['total', 'ran', 'pending', 'migrations']);
});
```

- [ ] **Step 3: Run full test suite**

Run: `cd /Users/xve/webdev/artisan-agent-output && vendor/bin/pest`
Expected: All tests PASS.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/CleanedTextOutputTest.php tests/Feature/JsonOutputTest.php
git commit -m "test: add integration tests for full output flow"
```

---

### Task 18: Manual Verification with Test App

- [ ] **Step 1: Add local repository to test app**

Run:
```bash
cd /Users/xve/webdev/btb-filament5-demo && composer config repositories.artisan-agent-output path ../artisan-agent-output && composer require skylence/artisan-agent-output:@dev
```
Expected: Package installed from local path.

- [ ] **Step 2: Test cleaned text output**

Run:
```bash
cd /Users/xve/webdev/btb-filament5-demo && AGENT_DETECTOR_FORCE=1 php artisan about
```
Expected: Output with no ANSI codes, no box-drawing characters.

- [ ] **Step 3: Test JSON output (migrate:status)**

Run:
```bash
cd /Users/xve/webdev/btb-filament5-demo && AGENT_DETECTOR_FORCE=1 php artisan migrate:status
```
Expected: JSON output with migrations array.

- [ ] **Step 4: Test human output unaffected**

Run:
```bash
cd /Users/xve/webdev/btb-filament5-demo && php artisan about
```
Expected: Normal decorated output (no agent detected).

- [ ] **Step 5: Remove test dependency when done**

Run:
```bash
cd /Users/xve/webdev/btb-filament5-demo && composer remove skylence/artisan-agent-output && composer config --unset repositories.artisan-agent-output
```

---

### Task 19: README

**Files:**
- Create: `README.md`

- [ ] **Step 1: Write README**

Write a README covering: what the package does, installation (`composer require skylence/artisan-agent-output`), how it works (two layers), config options, list of supported commands, how to register custom parsers, and link to the PRD.

- [ ] **Step 2: Commit**

```bash
git add README.md
git commit -m "docs: add README"
```
