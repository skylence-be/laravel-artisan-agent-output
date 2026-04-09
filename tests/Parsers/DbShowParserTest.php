<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\Parsers\DbShowParser;

it('returns database info as structured data', function () {
    $parser = new DbShowParser();
    $result = $parser->parse($this->app);

    expect($result)->toHaveKeys(['platform', 'tables']);
    expect($result['platform'])->toHaveKeys(['connection', 'name']);
    expect($result['tables'])->toBeArray();
});
