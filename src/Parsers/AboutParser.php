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
