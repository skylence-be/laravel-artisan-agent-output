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
