<?php

declare(strict_types=1);

it('cleans artisan about output', function () {
    $this->artisan('about')
        ->assertSuccessful();
});
