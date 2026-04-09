# PRD: artisan-agent-output

## Problem

AI coding agents (Claude Code, Cursor, Devin, Gemini CLI) waste significant tokens on Artisan command output. Laravel Artisan outputs ANSI colors, box-drawing characters, decorative tables, and verbose formatting — all meaningless to an LLM. No package exists that optimizes Artisan output specifically for AI agents.

Laravel Boost helps agents discover commands. PAO optimizes test runner output. Neither optimizes what Artisan commands actually return.

## Solution

`skylence/artisan-agent-output` — a Laravel package that detects AI agents and optimizes Artisan command output in two modes:

1. **Cleaned text (default)** — strips ANSI codes, box-drawing characters, excess whitespace from all commands. Universal, zero-config.
2. **Structured JSON (opt-in per command)** — replaces output with compact JSON for commands that have a registered parser. Ships with ~10 parsers for core Laravel commands. Third-party packages can register their own.

## Goals

- Reduce Artisan output token consumption by 40-90%
- Zero impact on human workflows — only activates when an AI agent is detected
- Zero config — install and it works
- Extensible — third-party packages can register JSON parsers via a facade
- Complement (not compete with) PAO and Laravel Boost

## Non-Goals

- Parsing test runner output (PAO does this)
- Providing MCP tools for Artisan (Boost does this)
- Modifying command behavior — only output formatting changes
- Supporting non-Laravel PHP projects

## Target Users

- Developers using AI coding agents with Laravel projects
- Package authors who want their Artisan commands to be agent-friendly

## Architecture

### Two-Layer System

**Layer 1: OutputStyle Override (universal)**
A custom `AgentOutputStyle` extends Laravel's `OutputStyle` and pipes all `write()`/`writeln()` calls through `OutputCleaner`. This handles every Artisan command without any per-command logic.

**Layer 2: Command Parsers (per-command JSON)**
For commands with registered parsers, the package listens to `CommandStarting` to buffer output and `CommandFinished` to run the parser and emit structured JSON instead. Parsers query Laravel internals directly (e.g., the Migrator, Router) rather than re-parsing rendered text.

### Registration API

```php
use Skylence\ArtisanAgentOutput\Facades\AgentOutput;

AgentOutput::register('horizon:status', HorizonStatusParser::class);
```

### Parser Contract

```php
interface CommandParser
{
    public function parse(Application $app): array;
}
```

### Config

```php
return [
    'json' => true,        // Enable JSON parsers (false = cleaned text only)
    'exclude' => [],        // Commands to skip entirely
];
```

## Core Parsers (v1)

| Command | JSON Output |
|---------|------------|
| `about` | App name, versions, environment, debug, drivers |
| `migrate:status` | Migrations array with name, status, batch |
| `route:list` | Routes with method, URI, name, action, middleware |
| `db:show` | Connection info, tables with sizes |
| `db:table` | Columns with type, nullable, default, indexes |
| `schedule:list` | Tasks with command, expression, next run |
| `model:show` | Attributes, relations, casts, scopes |
| `queue:failed` | Failed jobs with ID, connection, queue, exception |
| `event:list` | Events with listeners |
| `config:show` | Key-value config pairs |

## Edge Cases

- No agent detected: package does nothing, zero overhead
- Parser throws: falls back to cleaned text, logs warning
- No parser registered: cleaned text (default)
- `AGENT_OUTPUT_DISABLE` env var: kill switch
- `--help` / `--version` flags: skip processing

## Success Metrics

- Token reduction benchmarks comparable to PAO's 44-75% for Artisan
- All 10 core parsers producing valid, minimal JSON
- Third-party registration working with at least one external package
- Zero test failures when running a Laravel app's test suite with the package installed

## Dependencies

- PHP ^8.3
- Laravel ^12.0 || ^13.0
- `shipfastlabs/agent-detector` ^1.1.0

## Timeline

v0.1.0 — Core: OutputCleaner, AgentOutputStyle, ServiceProvider, config, 3 parsers (about, migrate:status, route:list)
v0.2.0 — Remaining 7 core parsers
v0.3.0 — Extensibility API (facade, third-party registration)
v1.0.0 — Stable release with full test coverage
