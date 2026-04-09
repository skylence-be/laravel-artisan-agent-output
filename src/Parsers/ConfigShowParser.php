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
