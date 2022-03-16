<?php

declare(strict_types=1);

namespace Attlaz\Project\Cli\Command;

use Attlaz\Client;
use Attlaz\Project\App\Environment;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class SystemSetup extends Command
{

    protected $client;
    protected $environment;
    protected $logger;

    public function __construct(Client $client, Environment $environment, LoggerInterface $logger)
    {
        parent::__construct();

        $this->client = $client;
        $this->environment = $environment;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('system:setup')
            ->setDescription('Setup the system')
            ->setHelp('Use this command to setup the system');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $values = [];
            $questionHelper = new QuestionHelper();

            /**
             * API Endpoint
             */
            $apiEndpoints = [
                'production' => 'https://api.attlaz.com',
                'staging'    => 'https://api2.attlaz.com',
            ];
            $question = new ChoiceQuestion('API endpoint?', $apiEndpoints, $apiEndpoints['production']);

            $answer = $questionHelper->ask($input, $output, $question);

            $apiEndpoint = $apiEndpoints['production'];
            foreach ($apiEndpoints as $key => $value) {
                if ($answer === $key || $answer === $value) {
                    $apiEndpoint = $value;
                }
            }
            //TODO: handle when answer is not a valid option

            /**
             * API Client id
             */
            $question = new Question('API client id?');
            $question->setValidator(function ($answer) {
                if (!is_string($answer) || empty($answer)) {
                    throw new \RuntimeException('The API client id cannot be empty');
                }

                return $answer;
            });

            $apiClientId = $questionHelper->ask($input, $output, $question);

            /**
             * API Client secret
             */
            $question = new Question('API client secret?');
            $question->setHidden(true);
            $question->setValidator(function ($answer) {
                if (!is_string($answer) || empty($answer)) {
                    throw new \RuntimeException('The API client secret cannot be empty');
                }

                return $answer;
            });

            $apiClientSecret = $questionHelper->ask($input, $output, $question);

            //TODO: handle when client is not able to connect to API

            $client = new Client($apiClientId, $apiClientSecret);
            $client->setEndPoint($apiEndpoint);
            $values[Environment::ENV_API_ENDPOINT] = $apiEndpoint;
            $values[Environment::ENV_API_CLIENT_ID] = $apiClientId;
            $values[Environment::ENV_API_CLIENT_SECRET] = $apiClientSecret;

            /**
             * Project
             */
            $projects = $client->getProjects();
            $arrProjectOptions = [];
            foreach ($projects as $project) {
                $arrProjectOptions[$project->id] = $project->name;
            }

            $question = new ChoiceQuestion('Project?', $arrProjectOptions);
            $question->setValidator(function ($answer) {
                if (!is_string($answer) || \strlen($answer) !== 9) {
                    throw new \RuntimeException('The project identifier should be 9 characters');
                }

                return $answer;
            });
            $answer = $questionHelper->ask($input, $output, $question);
            foreach ($arrProjectOptions as $key => $value) {
                if ($answer === $key || $answer === $value) {
                    $values[Environment::ENV_PROJECT] = $key;
                }
            }

            if (!isset($values[Environment::ENV_PROJECT])) {
                throw new \Exception('Invalid project');
            }

            /**
             * Project environment
             */
            $projectEnvironments = $client->getProjectEnvironments((string)$values[Environment::ENV_PROJECT]);
            $arrProjectEnvironmentOptions = [];
            foreach ($projectEnvironments as $projectEnvironment) {
                $arrProjectEnvironmentOptions[$projectEnvironment->id] = $projectEnvironment->name;
            }

            $question = new ChoiceQuestion('Project environment?', $arrProjectEnvironmentOptions);
            $answer = $questionHelper->ask($input, $output, $question);
            foreach ($arrProjectEnvironmentOptions as $key => $value) {
                if ($answer === $key || $answer === $value) {
                    $values[Environment::ENV_PROJECT_ENVIRONMENT] = $key;
                }
            }

            /**
             * Mode
             */
            $question = new ChoiceQuestion('Mode', [
                'development',
                'production',
            ], 'development');
            $question->setErrorMessage('Mode %s is invalid.');

            $values[Environment::ENV_MODE] = $questionHelper->ask($input, $output, $question);

            /**
             * Storage
             */ //            $question = new Question('Storage');
            //            $question->setValidator(function ($answer) {
            //                if (!is_string($answer) || empty($answer)) {
            //                    throw new \RuntimeException('The storage key cannot be empty');
            //                }
            //
            //                return $answer;
            //            });

//            $values[Environment::ENV_STORAGE] = 'mongodb://mongo-admin:UCGJbmQ25Kdx@hq.attlaz.com:7285';

            $this->saveEnv($values);

            //TODO: test environment variables by connecting to the API

            return 1;
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());

            return 1;
        }
    }

    private function saveEnv(array $values): void
    {
        $strEnv = '';

        foreach ($values as $key => $value) {
            $strEnv .= $key . '=' . $value . \PHP_EOL;
        }

        \file_put_contents($this->environment->getEnvFilePath(), $strEnv);
    }
}
