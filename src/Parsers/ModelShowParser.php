<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class ModelShowParser implements CommandParser
{
    public function parse(Application $app): array
    {
        $argv = array_values(array_filter((array) ($_SERVER['argv'] ?? []), is_string(...)));
        $model = null;

        foreach ($argv as $i => $arg) {
            $next = $argv[$i + 1] ?? null;
            if ($arg === 'model:show' && $next !== null && ! str_starts_with($next, '-')) {
                $model = $next;
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
        Artisan::call('model:show', ['model' => $model, '--json' => true]);
        $output = Artisan::output();

        /** @var array<string, mixed> $data */
        $data = json_decode(mb_trim($output), true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }
}
