<?php

declare(strict_types=1);

use Skylence\ArtisanAgentOutput\Parsers\QueueFailedParser;

it('returns empty list when no failed jobs', function () {
    $parser = new QueueFailedParser();
    $result = $parser->parse($this->app);

    expect($result)->toHaveKeys(['total', 'jobs']);
    expect($result['total'])->toBe(0);
    expect($result['jobs'])->toBe([]);
});
