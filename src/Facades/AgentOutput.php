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
