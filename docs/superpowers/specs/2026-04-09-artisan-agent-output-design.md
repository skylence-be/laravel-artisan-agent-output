# Design Spec: skylence/artisan-agent-output

## Overview

A Laravel package that optimizes Artisan command output for AI coding agents. Two modes: cleaned text (universal default) and structured JSON (per-command parsers). Extensible via a facade for third-party packages.

## Package Structure

```
skylence/artisan-agent-output
├── src/
│   ├── ServiceProvider.php
│   ├── AgentOutputStyle.php
│   ├── OutputCleaner.php
│   ├── ParserRegistry.php
│   ├── Facades/
│   │   └── AgentOutput.php
│   ├── Contracts/
│   │   └── CommandParser.php
│   └── Parsers/
│       ├── AboutParser.php
│       ├── MigrateStatusParser.php
│       ├── RouteListParser.php
│       ├── DbShowParser.php
│       ├── DbTableParser.php
│       ├── ScheduleListParser.php
│       ├── ModelShowParser.php
│       ├── QueueFailedParser.php
│       ├── EventListParser.php
│       └── ConfigShowParser.php
├── config/
│   └── artisan-agent-output.php
├── composer.json
├── docs/
│   └── prd.md
└── tests/
    ├── Unit/
    │   ├── OutputCleanerTest.php
    │   └── ParserRegistryTest.php
    ├── Feature/
    │   ├── ServiceProviderTest.php
    │   └── CleanedTextOutputTest.php
    └── Parsers/
        ├── AboutParserTest.php
        ├── MigrateStatusParserTest.php
        ├── RouteListParserTest.php
        ├── DbShowParserTest.php
        ├── DbTableParserTest.php
        ├── ScheduleListParserTest.php
        ├── ModelShowParserTest.php
        ├── QueueFailedParserTest.php
        ├── EventListParserTest.php
        └── ConfigShowParserTest.php
```

## Component Details

### ServiceProvider

```php
final class ServiceProvider extends LaravelServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/artisan-agent-output.php', 'artisan-agent-output');
        $this->app->singleton(ParserRegistry::class);
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) return;
        if (isset($_SERVER['AGENT_OUTPUT_DISABLE'])) return;
        if (! AgentDetector::detect()->isAgent) return;

        $this->publishes([
            __DIR__.'/../config/artisan-agent-output.php' => config_path('artisan-agent-output.php'),
        ]);

        // Layer 1: Cleaned text for all commands
        $this->app->bind(OutputStyle::class, AgentOutputStyle::class);

        $events = $this->app->make(Dispatcher::class);
        $events->listen(CommandStarting::class, function (CommandStarting $event) use ($registry) {
            $event->output->setDecorated(false);

            // If a JSON parser exists, swap to buffered output to suppress text
            if (config('artisan-agent-output.json', true)
                && $event->command
                && $registry->has($event->command)
                && ! in_array($event->command, config('artisan-agent-output.exclude', []))) {
                // Store real output, replace with buffer
                $this->realOutput = $event->output;
                $event->output = new BufferedOutput();
            }
        });

        // Register core parsers
        $registry = $this->app->make(ParserRegistry::class);
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
        if (config('artisan-agent-output.json', true)) {
            $events->listen(CommandFinished::class, function (CommandFinished $event) use ($registry) {
                $command = $event->command;
                if (! $command || ! $registry->has($command)) return;
                if (in_array($command, config('artisan-agent-output.exclude', []))) return;

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
                    // Fallback: flush buffered text to real output
                    if ($event->output instanceof BufferedOutput && isset($this->realOutput)) {
                        $this->realOutput->write($event->output->fetch());
                    }
                }

                $this->realOutput = null;
            });
        }
    }
}
```

### AgentOutputStyle

```php
final class AgentOutputStyle extends OutputStyle
{
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $output->setDecorated(false);
        parent::__construct($input, $output);
    }

    public function write(string|iterable $messages, bool $newline = false, int $options = 0): void
    {
        parent::write($this->clean($messages), $newline, $options);
    }

    public function writeln(string|iterable $messages, int $type = self::OUTPUT_NORMAL): void
    {
        parent::writeln($this->clean($messages), $type);
    }

    private function clean(string|iterable $messages): string|array
    {
        if (is_string($messages)) {
            return OutputCleaner::clean($messages);
        }
        return array_values(array_map(OutputCleaner::clean(...), [...$messages]));
    }
}
```

### OutputCleaner

```php
final class OutputCleaner
{
    public static function clean(string $output): string
    {
        // Strip ANSI escape codes
        $output = (string) preg_replace('/\e\[[0-9;]*[A-Za-z]/', '', $output);
        // Strip control characters (except newline, carriage return)
        $output = (string) preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $output);
        // Strip Unicode replacement character
        $output = (string) preg_replace('/\x{FFFD}/u', '', $output);
        // Strip box-drawing and decorative Unicode
        $output = (string) preg_replace('/[─━│┌┐└┘├┤┬┴┼▓░▒═║╔╗╚╝╠╣╦╩╬➜▶►⚠✖✔●◆■▪→←↑↓▕⨯✕]+/u', '', $output);
        // Collapse dots
        $output = (string) preg_replace('/\.{3,}/', '..', $output);
        // Collapse whitespace
        $output = (string) preg_replace('/[ \t]+/', ' ', $output);
        // Collapse blank lines
        return (string) preg_replace('/\n\s*\n/', "\n", $output);
    }
}
```

### CommandParser Contract

```php
interface CommandParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(Application $app): array;
}
```

### ParserRegistry

```php
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
}
```

### Facade

```php
final class AgentOutput extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ParserRegistry::class;
    }
}
```

### Example Parser: MigrateStatusParser

```php
final class MigrateStatusParser implements CommandParser
{
    public function parse(Application $app): array
    {
        $migrator = $app->make('migrator');
        $repository = $migrator->getRepository();
        $ran = $repository->getRan();
        $batches = $repository->getMigrationBatches();
        $files = $migrator->getMigrationFiles($migrator->paths());

        $migrations = [];
        foreach ($files as $name => $path) {
            $isRan = in_array($name, $ran);
            $migrations[] = [
                'name' => $name,
                'status' => $isRan ? 'ran' : 'pending',
                'batch' => $batches[$name] ?? null,
            ];
        }

        return [
            'total' => count($migrations),
            'ran' => count($ran),
            'pending' => count($migrations) - count($ran),
            'migrations' => $migrations,
        ];
    }
}
```

### Config

```php
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

## Data Flow

```
1. Composer autoload → ServiceProvider auto-discovered
2. ServiceProvider::boot()
   ├── Agent detected? No → return (zero overhead)
   └── Yes:
       ├── Bind AgentOutputStyle (Layer 1 — all commands get cleaned text)
       ├── Register core parsers in ParserRegistry
       └── Listen to CommandFinished (Layer 2 — JSON where parsers exist)
3. User/agent runs: php artisan migrate:status
4. CommandStarting fires → setDecorated(false)
5. Command executes → output goes through AgentOutputStyle::write/writeln → cleaned
6. CommandFinished fires → MigrateStatusParser::parse() → JSON emitted
```

## JSON Parser Suppression Strategy

When a JSON parser runs on `CommandFinished`, the original command output has already been written to the output buffer via `AgentOutputStyle` (cleaned text). The JSON parser appends structured JSON after it.

To suppress the cleaned text when JSON is available, the `CommandStarting` listener should swap the output to a `BufferedOutput` when a parser exists for the command. On `CommandFinished`, the buffer is discarded and only the JSON is written to the real output.

## Dependencies

```json
{
    "require": {
        "php": "^8.3",
        "illuminate/console": "^12.0 || ^13.0",
        "illuminate/support": "^12.0 || ^13.0",
        "shipfastlabs/agent-detector": "^1.1.0"
    }
}
```

## Testing Approach

- **Unit**: OutputCleaner (ANSI, box chars, whitespace), ParserRegistry (register, has, get)
- **Per-parser**: Mock Laravel services, assert JSON structure and keys
- **Integration**: Boot ServiceProvider with agent env, run Artisan command via `$this->artisan()`, assert output format
- **Fallback**: Parser throws → assert cleaned text output, warning logged
- Use `orchestra/testbench` for Laravel integration testing
