# Artisan Agent Output

Agent-optimized output for Laravel Artisan commands. Detects AI coding agents and replaces verbose, decorated output with clean text or structured JSON.

## Installation

```bash
composer require skylence/artisan-agent-output
```

Zero config. The package auto-discovers its service provider and activates only when an AI agent is detected (Claude Code, Cursor, Devin, Gemini CLI, etc.).

## How It Works

Two-layer system:

### Layer 1: Cleaned Text (all commands)

Strips ANSI colors, box-drawing characters, decorative Unicode, and excess whitespace from all Artisan output. This is the universal default that works with every command.

### Layer 2: Structured JSON (commands with parsers)

For commands with registered parsers, output is replaced with compact structured JSON. Ships with parsers for 10 core Laravel commands.

## Supported Commands

| Command | JSON Output |
|---------|------------|
| `about` | App name, versions, environment, debug, drivers |
| `migrate:status` | Migrations with name, status, batch |
| `route:list` | Routes with method, URI, name, action, middleware |
| `db:show` | Connection info, tables with sizes |
| `db:table` | Columns, indexes, foreign keys |
| `schedule:list` | Tasks with command, expression, next run |
| `model:show` | Attributes, relations, casts, events |
| `queue:failed` | Failed jobs with ID, connection, queue, exception |
| `event:list` | Events with listeners |
| `config:show` | Configuration key-value pairs |

All other commands get cleaned text output automatically.

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --provider="Skylence\ArtisanAgentOutput\ServiceProvider"
```

```php
return [
    // Set to false to disable JSON parsers (cleaned text only)
    'json' => true,

    // Commands to exclude from JSON parsing
    'exclude' => [],
];
```

## Registering Custom Parsers

Third-party packages can register their own parsers:

```php
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;
use Skylence\ArtisanAgentOutput\Facades\AgentOutput;

// In your service provider's boot() method:
AgentOutput::register('horizon:status', HorizonStatusParser::class);
```

Implement the `CommandParser` interface:

```php
use Illuminate\Contracts\Foundation\Application;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

class HorizonStatusParser implements CommandParser
{
    public function parse(Application $app): array
    {
        // Query Laravel services directly and return structured data
        return [
            'status' => 'running',
            'processes' => 4,
        ];
    }
}
```

## Kill Switch

Set the `AGENT_OUTPUT_DISABLE` environment variable to bypass all processing:

```bash
AGENT_OUTPUT_DISABLE=1 php artisan about
```

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## License

MIT
