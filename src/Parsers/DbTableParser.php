<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class DbTableParser implements CommandParser
{
    public function parse(Application $app): array
    {
        $argv = array_values(array_filter((array) ($_SERVER['argv'] ?? []), 'is_string'));
        $table = null;

        foreach ($argv as $i => $arg) {
            $next = $argv[$i + 1] ?? null;
            if ($arg === 'db:table' && $next !== null && ! str_starts_with($next, '-')) {
                $table = $next;
                break;
            }
        }

        if ($table === null) {
            return ['error' => 'No table specified'];
        }

        return $this->parseTable($app, $table);
    }

    /** @return array<string, mixed> */
    public function parseTable(Application $app, string $table): array
    {
        /** @var \Illuminate\Database\DatabaseManager $manager */
        $manager = $app->make('db');
        /** @var Connection $connection */
        $connection = $manager->connection();
        $schema = $connection->getSchemaBuilder();

        $columns = array_map(fn (array $col): array => [
            'name' => $col['name'],
            'type' => $col['type_name'],
            'nullable' => $col['nullable'],
            'default' => $col['default'],
            'auto_increment' => $col['auto_increment'],
        ], $schema->getColumns($table));

        $indexes = array_map(fn (array $idx): array => [
            'name' => $idx['name'],
            'columns' => $idx['columns'],
            'unique' => $idx['unique'],
            'primary' => $idx['primary'],
        ], $schema->getIndexes($table));

        /** @var array<int, array<string, mixed>> $rawForeignKeys */
        $rawForeignKeys = $schema->getForeignKeys($table);
        $foreignKeys = array_map(fn (array $fk): array => [
            'name' => $fk['name'],
            'columns' => $fk['columns'],
            'foreign_table' => $fk['foreign_table'],
            'foreign_columns' => $fk['foreign_columns'],
            'on_update' => $fk['on_update'] ?? null,
            'on_delete' => $fk['on_delete'] ?? null,
        ], $rawForeignKeys);

        return [
            'table' => $table,
            'columns' => $columns,
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys,
        ];
    }
}
