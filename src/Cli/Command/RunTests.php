<?php
declare(strict_types=1);

namespace Attlaz\Project\Cli\Command;

use Attlaz\Project\Logger\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunTests extends Command
{

    protected $logger;

    public function __construct(Logger $logger)
    {
        parent::__construct();

        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('test:run')
             ->setDescription('Run tests')
             ->setHelp('This command allows you to run tests');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return 1;
    }

}
