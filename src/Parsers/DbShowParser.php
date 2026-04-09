<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConnectionResolverInterface;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class DbShowParser implements CommandParser
{
    public function parse(Application $app): array
    {
        /** @var ConnectionResolverInterface $connections */
        $connections = $app->make('db');
        $connection = $connections->connection();
        $schema = $connection->getSchemaBuilder();

        $tables = array_map(fn (array $table): array => [
            'name' => $table['name'],
            'schema' => $table['schema'] ?? null,
            'size' => $table['size'] ?? null,
            'engine' => $table['engine'] ?? null,
            'collation' => $table['collation'] ?? null,
            'comment' => $table['comment'] ?? null,
        ], $schema->getTables());

        $platform = [
            'connection' => $connection->getName(),
            'name' => $connection->getDriverTitle(),
        ];

        if (method_exists($connection, 'getServerVersion')) {
            try {
                $platform['server_version'] = $connection->getServerVersion();
            } catch (\Throwable) {
                // Not available on all connection types
            }
        }

        return [
            'platform' => $platform,
            'tables' => $tables,
        ];
    }
}
