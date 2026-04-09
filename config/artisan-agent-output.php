<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | JSON Mode
    |--------------------------------------------------------------------------
    |
    | When true, commands with registered parsers will output structured JSON
    | instead of cleaned text. When false, all commands get cleaned text only.
    |
    */
    'json' => true,

    /*
    |--------------------------------------------------------------------------
    | Excluded Commands
    |--------------------------------------------------------------------------
    |
    | Commands listed here will not be processed by JSON parsers, even if a
    | parser is registered. They will still receive cleaned text output.
    |
    */
    'exclude' => [],
];
