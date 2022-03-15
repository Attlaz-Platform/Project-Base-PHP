<?php
declare(strict_types=1);

namespace Attlaz\Project\Storage;

use Attlaz\Client;
use Attlaz\Project\App\Environment;

class StorageManager
{
    /** @var StorageEngine */
    public $cache;
    /** @var StorageEngine */
    public $vault;
    /** @var StorageEngine */
    public $persistent;

    public function __construct(Client $attlazClient, Environment $environment)
    {
        $this->cache = new StorageEngine($attlazClient, $environment, 'cache');
        $this->vault = new StorageEngine($attlazClient, $environment, 'vault');
        $this->persistent = new StorageEngine($attlazClient, $environment, 'persistent');
    }
}
