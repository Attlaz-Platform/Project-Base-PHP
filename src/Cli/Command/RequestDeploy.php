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
            ->setAliases([
                'deployment:request',
                'request:deploy',
                'deploy',
            ])
            ->setDescription('Request deploy.')
            ->setHelp('This command allows you to request a deployment')
            ->addArgument('environment', InputArgument::OPTIONAL, 'Environment key');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            //TODO: get environment by id or key

            $environmentIdentifier = $input->getArgument('environment');


            $selectedEnvironment = null;
            if (!empty($environmentIdentifier)) {
                $selectedEnvironment = $this->client->getProjectEnvironmentByKey($this->environment->getProject()->id, $environmentIdentifier);
            } else {
                $environmentIdentifier = $this->environment->getProject()->defaultEnvironmentId;
                $selectedEnvironment = $this->client->getProjectEnvironmentById($environmentIdentifier);
            }


            if (\is_null($selectedEnvironment)) {
                $options = [];
                $projectId = $this->environment->getProject()->id;
                $environments = $this->client->getProjectEnvironments($projectId);
                foreach ($environments as $environment) {
                    $options[] = $environment->key;
                }
                throw new \Exception('Unable to request deploy: environment not found (available: ' . \implode(', ', $options) . ')');
            }

            $deployId = $this->client->requestDeploy($selectedEnvironment->id);

            $deployUrl = $this->environment->getAppUrl($selectedEnvironment, ['manage']);

            $this->logger->info('Deploy requested for environment "' . $selectedEnvironment->name . '" (more: ' . $deployUrl . ')');
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());

            return 1;
        }
    }
}
