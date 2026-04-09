<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class ConfigShowParser implements CommandParser
{
    public function parse(Application $app): array
    {
        $argv = array_values(array_filter((array) ($_SERVER['argv'] ?? []), is_string(...)));
        $key = null;

        foreach ($argv as $i => $arg) {
            $next = $argv[$i + 1] ?? null;
            if ($arg === 'config:show' && $next !== null && ! str_starts_with($next, '-')) {
                $key = $next;
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
