<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConnectionResolverInterface;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class DbTableParser implements CommandParser
{
    public function parse(Application $app): array
    {
        $argv = $_SERVER['argv'] ?? [];
        $table = null;

        foreach ($argv as $i => $arg) {
            if ($arg === 'db:table' && isset($argv[$i + 1]) && ! str_starts_with((string) $argv[$i + 1], '-')) {
                $table = $argv[$i + 1];
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
        /** @var ConnectionResolverInterface $connections */
        $connections = $app->make('db');
        $schema = $connections->connection()->getSchemaBuilder();

        $columns = array_map(fn (array $col): array => [
            'name' => $col['name'],
            'type' => $col['type_name'] ?? $col['type'],
            'nullable' => $col['nullable'] ?? false,
            'default' => $col['default'] ?? null,
            'auto_increment' => $col['auto_increment'] ?? false,
        ], $schema->getColumns($table));

        $indexes = array_map(fn (array $idx): array => [
            'name' => $idx['name'],
            'columns' => $idx['columns'],
            'unique' => $idx['unique'] ?? false,
            'primary' => $idx['primary'] ?? false,
        ], $schema->getIndexes($table));

        $foreignKeys = array_map(fn (array $fk): array => [
            'name' => $fk['name'],
            'columns' => $fk['columns'],
            'foreign_table' => $fk['foreign_table'],
            'foreign_columns' => $fk['foreign_columns'],
            'on_update' => $fk['on_update'] ?? null,
            'on_delete' => $fk['on_delete'] ?? null,
        ], $schema->getForeignKeys($table));

        return [
            'table' => $table,
            'columns' => $columns,
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys,
        ];
    }
}
