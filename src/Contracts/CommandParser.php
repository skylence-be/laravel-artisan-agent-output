<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Contracts;

use Illuminate\Contracts\Foundation\Application;

interface CommandParser
{
    /** @return array<string, mixed> */
    public function parse(Application $app): array;
}
