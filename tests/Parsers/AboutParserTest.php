<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\Parsers\AboutParser;

it('returns structured about data', function () {
    $parser = new AboutParser;
    $result = $parser->parse($this->app);

    expect($result)->toHaveKeys(['environment', 'cache', 'drivers']);
    expect($result['environment'])->toBeArray();
    expect($result['environment'])->toHaveKey('laravel_version');
});
