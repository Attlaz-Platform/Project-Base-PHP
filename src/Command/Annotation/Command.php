<?php
declare(strict_types=1);

namespace Attlaz\Project\Command\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * "Command" annotation.
 *
 * Marks a class as a command
 *
 * @Annotation
 * @Target("CLASS")
 * @author Stijn Duynslaeger <stijn@attlaz.com>
 */
final class Command
{

    /**
     * Id of the task
     * @var string
     * @Required
     */
    public $task;

    /**
     * @return string
     */
    public function getTask(): string
    {
        return $this->task;
    }

}
