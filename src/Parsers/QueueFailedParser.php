<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput\Parsers;

use Illuminate\Contracts\Foundation\Application;
use Skylence\ArtisanAgentOutput\Contracts\CommandParser;
use Throwable;

final class QueueFailedParser implements CommandParser
{
    public function parse(Application $app): array
    {
        try {
            /** @var \Illuminate\Queue\Failed\FailedJobProviderInterface $failer */
            $failer = $app->make('queue.failer');
            $failed = $failer->all();
        } catch (Throwable) {
            return [
                'total' => 0,
                'jobs' => [],
            ];
        }

        $jobs = [];
        foreach ($failed as $job) {
            $payload = json_decode((string) $job->payload, true);

            $jobs[] = [
                'id' => $job->id,
                'connection' => $job->connection,
                'queue' => $job->queue,
                'class' => $payload['displayName'] ?? 'Unknown',
                'failed_at' => $job->failed_at,
                'exception' => mb_substr((string) $job->exception, 0, 500),
            ];
        }

        return [
            'total' => count($jobs),
            'jobs' => $jobs,
        ];
    }
}
