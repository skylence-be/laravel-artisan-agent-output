<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;
use Throwable;

final class DbShowParser implements CommandParser
{
    public function parse(Application $app): array
    {
        /** @var Connection $connection */
        $connection = $app->make('db')->connection();
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

        try {
            $platform['server_version'] = $connection->getServerVersion();
        } catch (Throwable) {
            // Not available on all connection types
        }

        return [
            'platform' => $platform,
            'tables' => $tables,
        ];
    }
}
