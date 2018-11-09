<?php
declare(strict_types=1);

namespace Attlaz\Project\App;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;

class Config
{

    public const MODE_PRODUCTION = 'production';
    public const MODE_DEVELOPMENT = 'development';

    public $branch;
    public $mode;
    public $definitionsFile;

    public $api_endpoint;
    public $api_client_id;
    public $api_client_secret;

    public $storage;

    public function __construct(string $projectRootPath)
    {
        //TODO: handle that env file is not readable
        try {
            $dotenv = new Dotenv($projectRootPath);
            $dotenv->load();
        } catch (InvalidPathException $ex) {
            throw new \Exception('Unable to start project: .env file missing in directory "' . $projectRootPath . '"');
        }

        $this->branch = $this->getEnvValue('project');
        $this->mode = $this->getEnvValue('mode');
        if ($this->mode !== Config::MODE_PRODUCTION && $this->mode !== Config::MODE_DEVELOPMENT) {
            throw new \InvalidArgumentException('Invalid mode "' . $this->mode . '", must be "' . Config::MODE_PRODUCTION . '" or "' . Config::MODE_DEVELOPMENT . '"');
        }

        $this->api_endpoint = $this->getEnvValue('api_endpoint');
        $this->api_client_id = $this->getEnvValue('api_client_id');
        $this->api_client_secret = $this->getEnvValue('api_client_secret');

        $this->storage = $this->getEnvValue('storage');

        $diFile = $projectRootPath . \DIRECTORY_SEPARATOR . 'src' . \DIRECTORY_SEPARATOR . 'App' . \DIRECTORY_SEPARATOR . 'etc' . \DIRECTORY_SEPARATOR . 'di.php';
        if (\file_exists($diFile)) {
            $this->definitionsFile = $diFile;
        }
    }

    private function getEnvValue(string $key)
    {
        $value = \getenv($key);
        if ($value === false) {
            throw new \Exception('Config variable "' . $key . '" not defined');
        }

        return (string)$value;
    }

    public function getBranchNameSafe():string
    {
        return \strtolower(\str_replace([' '], '_', $this->branch));
    }

}