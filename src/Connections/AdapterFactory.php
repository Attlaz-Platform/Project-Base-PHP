<?php

declare(strict_types=1);

namespace Attlaz\Project\Connections;

use Attlaz\Project\App\Config;

interface AdapterFactory
{
    public function getDefinitions(Config $config = null): array;
}
