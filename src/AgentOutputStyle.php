<?php

declare(strict_types=1);

namespace Skylence\ArtisanAgentOutput;

use Illuminate\Console\OutputStyle;
use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class AgentOutputStyle extends OutputStyle
{
    private static bool $muted = false;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $output->setDecorated(false);

        parent::__construct($input, $output);
    }

    public static function mute(): void
    {
        self::$muted = true;
    }

    public static function unmute(): void
    {
        self::$muted = false;
    }

    /** @param string|iterable<string> $messages */
    #[Override]
    public function write(string|iterable $messages, bool $newline = false, int $options = 0): void
    {
        if (self::$muted) {
            return;
        }

        parent::write($this->clean($messages), $newline, $options);
    }

    /** @param string|iterable<string> $messages */
    #[Override]
    public function writeln(string|iterable $messages, int $type = self::OUTPUT_NORMAL): void
    {
        if (self::$muted) {
            return;
        }

        parent::writeln($this->clean($messages), $type);
    }

    /** @return string|list<string> */
    private function clean(string|iterable $messages): string|array
    {
        if (is_string($messages)) {
            return OutputCleaner::clean($messages);
        }

        return array_values(array_map(
            OutputCleaner::clean(...),
            [...$messages],
        ));
    }
}
