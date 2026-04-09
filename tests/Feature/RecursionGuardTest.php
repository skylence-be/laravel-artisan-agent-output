<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\Parsers\AboutParser;

it('does not infinitely recurse when AboutParser calls Artisan::call', function () {
    $parser = new AboutParser;
    $result = $parser->parse($this->app);

    // If recursion guard fails, this test will stack overflow and never reach here
    expect($result)->toBeArray();
    expect($result)->toHaveKey('environment');
});
