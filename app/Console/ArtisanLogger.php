<?php

namespace App\Console;

use Illuminate\Support\Facades\App;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ArtisanLogger extends AbstractProcessingHandler
{
    private ?ConsoleOutput $output = null;
    private array $verbosityMapping = [
        100 => OutputInterface::VERBOSITY_DEBUG | OutputInterface::VERBOSITY_VERBOSE | OutputInterface::VERBOSITY_VERBOSE,
        200 => OutputInterface::VERBOSITY_NORMAL
            | OutputInterface::VERBOSITY_DEBUG | OutputInterface::VERBOSITY_VERBOSE | OutputInterface::VERBOSITY_VERBOSE,
        250 => OutputInterface::VERBOSITY_NORMAL
            | OutputInterface::VERBOSITY_DEBUG | OutputInterface::VERBOSITY_VERBOSE | OutputInterface::VERBOSITY_VERBOSE,
        300 => OutputInterface::VERBOSITY_QUIET | OutputInterface::VERBOSITY_NORMAL
            | OutputInterface::VERBOSITY_DEBUG | OutputInterface::VERBOSITY_VERBOSE | OutputInterface::VERBOSITY_VERBOSE,
        400 => OutputInterface::VERBOSITY_QUIET | OutputInterface::VERBOSITY_NORMAL
            | OutputInterface::VERBOSITY_DEBUG | OutputInterface::VERBOSITY_VERBOSE | OutputInterface::VERBOSITY_VERBOSE,
        500 => OutputInterface::VERBOSITY_QUIET | OutputInterface::VERBOSITY_NORMAL
            | OutputInterface::VERBOSITY_DEBUG | OutputInterface::VERBOSITY_VERBOSE | OutputInterface::VERBOSITY_VERBOSE,
        550 => OutputInterface::VERBOSITY_QUIET | OutputInterface::VERBOSITY_NORMAL
            | OutputInterface::VERBOSITY_DEBUG | OutputInterface::VERBOSITY_VERBOSE | OutputInterface::VERBOSITY_VERBOSE,
        600 => OutputInterface::VERBOSITY_QUIET | OutputInterface::VERBOSITY_NORMAL
            | OutputInterface::VERBOSITY_DEBUG | OutputInterface::VERBOSITY_VERBOSE | OutputInterface::VERBOSITY_VERBOSE,
    ];

    public function __construct($level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        if (!App::runningInConsole()) {
            return;
        }

        if ($this->output == null) {
            $this->output = new ConsoleOutput();
        }

        $this->output->write($record->formatted);
    }
}