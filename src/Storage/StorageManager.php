<?php

declare(strict_types=1);

namespace Attlaz\Project\Storage;

use Attlaz\Client;
use Attlaz\Project\App\Environment;

class StorageManager
{
    public StorageEngine $cache;
    public StorageEngine $vault;
    public StorageEngine $persistent;

    public function __construct(Client $attlazClient, Environment $environment)
    {
        $this->cache = new StorageEngine($attlazClient, $environment, 'cache');
        $this->vault = new StorageEngine($attlazClient, $environment, 'vault');
        $this->persistent = new StorageEngine($attlazClient, $environment, 'persistent');
    }
}
