<?php
declare(strict_types=1);

namespace Attlaz\Core\Command;

use Attlaz\Core\Model\Manager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ManagerCommand extends BaseCommand
{

    protected function configure()
    {
        parent::configure();
        $this->setName('manager:start')
             ->addArgument('message', InputArgument::REQUIRED)
             ->setDescription('Send message to queue')
             ->setHelp('This command allows you send a message to queue');
    }

    /** @var  OutputInterface */
    private $output;

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        $this->output = $output;

        $messageText = $input->getArgument('message');

        $manager = new Manager();
        $result = $manager->execute($messageText);

        $output->writeln($result);
    }

}