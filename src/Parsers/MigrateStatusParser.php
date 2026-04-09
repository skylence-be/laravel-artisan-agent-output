<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;

final class MigrateStatusParser implements CommandParser
{
    public function parse(Application $app): array
    {
        /** @var \Illuminate\Database\Migrations\Migrator $migrator */
        $migrator = $app->make('migrator');
        $repository = $migrator->getRepository();

        if (! $migrator->repositoryExists()) {
            return [
                'total' => 0,
                'ran' => 0,
                'pending' => 0,
                'migrations' => [],
            ];
        }

        $ran = $repository->getRan();
        $batches = $repository->getMigrationBatches();

        $paths = array_merge(
            $migrator->paths(),
            [$app->databasePath().DIRECTORY_SEPARATOR.'migrations'],
        );

        $files = $migrator->getMigrationFiles($paths);

        $migrations = [];
        foreach ($files as $name => $path) {
            $isRan = in_array($name, $ran, true);
            $migrations[] = [
                'name' => $name,
                'status' => $isRan ? 'ran' : 'pending',
                'batch' => $batches[$name] ?? null,
            ];
        }

        return [
            'total' => count($migrations),
            'ran' => count(array_filter($migrations, fn (array $m): bool => $m['status'] === 'ran')),
            'pending' => count(array_filter($migrations, fn (array $m): bool => $m['status'] === 'pending')),
            'migrations' => $migrations,
        ];
    }
}
