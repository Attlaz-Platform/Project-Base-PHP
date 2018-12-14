<?php
declare(strict_types=1);

namespace Attlaz\Project\Exception;

use Throwable;

class RuntimeException extends \Exception
{
    private $tags;

    public function __construct(string $message = "", array $tags = [], int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->tags = $tags;
    }

    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function getTags(): array
    {
        return $this->tags;
    }
}