<?php
declare(strict_types=1);

namespace Attlaz\Project\Helper;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class AbstractHelper implements LoggerAwareInterface
{
    use LoggerAwareTrait;
}
