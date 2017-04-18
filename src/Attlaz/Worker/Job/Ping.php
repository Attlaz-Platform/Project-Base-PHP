<?php
declare(strict_types=1);

namespace Attlaz\Worker\Job;

use Attlaz\Worker\Model\JobCommand;

class Ping extends JobCommand
{
    public function __invoke(string $input): array
    {
        return [
            'hostname' => gethostname(),
            'ip'       => gethostbyname(gethostname()),
            'input'    => $input,
        ];
    }
}