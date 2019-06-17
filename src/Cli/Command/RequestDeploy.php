<?php
declare(strict_types=1);

namespace Attlaz\Project\Cli\Command;

use Attlaz\Client;
use Attlaz\Project\App\Environment;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RequestDeploy extends Command
{
    protected $taskExecutor;
    protected $client;
    protected $environment;
    protected $logger;

    public function __construct(Environment $environment, Client $client, LoggerInterface $logger)
    {
        parent::__construct();
        $this->environment = $environment;
        $this->client = $client;

        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('deploy:request')
             ->setDescription('Request deploy.')
             ->setHelp('This command allows you to request a deployment')
             ->addArgument('environment', InputArgument::REQUIRED, 'Environment id or key');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            //TODO: get environment by id or key

            $environmentIdentifier = $input->getArgument('environment');

            if (\is_numeric($environmentIdentifier)) {
                $environmentIdentifier = \intval($environmentIdentifier);
                $environment = $this->client->getProjectEnvironmentById($environmentIdentifier);
            } else {
                $projectId = $this->environment->getProject()->id;
                $environment = $this->client->getProjectEnvironmentByKey($projectId, $environmentIdentifier);
            }
            if (\is_null($environment)) {
                throw new \Exception('Unable to request deploy: environment not found');
            }

            return $this->client->requestDeploy($environment->id);
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());

            return 1;
        }
    }
}
