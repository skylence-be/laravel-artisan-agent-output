<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput;

use AgentDetector\AgentDetector;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Skylence\ArtisanAgentOutput\Parsers\AboutParser;
use Skylence\ArtisanAgentOutput\Parsers\ConfigShowParser;
use Skylence\ArtisanAgentOutput\Parsers\DbShowParser;
use Skylence\ArtisanAgentOutput\Parsers\DbTableParser;
use Skylence\ArtisanAgentOutput\Parsers\EventListParser;
use Skylence\ArtisanAgentOutput\Parsers\MigrateStatusParser;
use Skylence\ArtisanAgentOutput\Parsers\ModelShowParser;
use Skylence\ArtisanAgentOutput\Parsers\QueueFailedParser;
use Skylence\ArtisanAgentOutput\Parsers\RouteListParser;
use Skylence\ArtisanAgentOutput\Parsers\ScheduleListParser;
use Symfony\Component\Console\Output\OutputInterface;

final class ServiceProvider extends LaravelServiceProvider
{
    private ?OutputInterface $realOutput = null;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/artisan-agent-output.php', 'artisan-agent-output');
        $this->app->singleton(ParserRegistry::class);
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        if (isset($_SERVER['AGENT_OUTPUT_DISABLE'])) {
            return;
        }

        if (! AgentDetector::detect()->isAgent) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/artisan-agent-output.php' => config_path('artisan-agent-output.php'),
        ]);

        // Layer 1: Cleaned text for all commands
        $this->app->bind(OutputStyle::class, AgentOutputStyle::class);

        $registry = $this->app->make(ParserRegistry::class);

        /** @var Dispatcher $events */
        $events = $this->app->make(Dispatcher::class);

        // Register core parsers
        $registry->register('about', AboutParser::class);
        $registry->register('migrate:status', MigrateStatusParser::class);
        $registry->register('route:list', RouteListParser::class);
        $registry->register('db:show', DbShowParser::class);
        $registry->register('db:table', DbTableParser::class);
        $registry->register('schedule:list', ScheduleListParser::class);
        $registry->register('model:show', ModelShowParser::class);
        $registry->register('queue:failed', QueueFailedParser::class);
        $registry->register('event:list', EventListParser::class);
        $registry->register('config:show', ConfigShowParser::class);

        $events->listen(CommandStarting::class, function (CommandStarting $event) use ($registry): void {
            $event->output->setDecorated(false);

            if ($this->shouldParseJson($event->command, $registry)) {
                $this->realOutput = $event->output;
            }
        });

        // Layer 2: JSON parsers for specific commands
        $events->listen(CommandFinished::class, function (CommandFinished $event) use ($registry): void {
            $command = $event->command;

            if (! $this->shouldParseJson($command, $registry)) {
                return;
            }

            $output = $this->realOutput ?? $event->output;

            try {
                $parser = $this->app->make($registry->get($command));
                $result = $parser->parse($this->app);
                $json = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $output->writeln($json);
            } catch (\Throwable $e) {
                logger()->warning("artisan-agent-output: parser failed for {$command}", [
                    'error' => $e->getMessage(),
                ]);
            }

            $this->realOutput = null;
        });
    }

    private function shouldParseJson(?string $command, ParserRegistry $registry): bool
    {
        if (! $command) {
            return false;
        }

        if (! config('artisan-agent-output.json', true)) {
            return false;
        }

        if (! $registry->has($command)) {
            return false;
        }

        return ! in_array($command, config('artisan-agent-output.exclude', []), true);
    }
}
