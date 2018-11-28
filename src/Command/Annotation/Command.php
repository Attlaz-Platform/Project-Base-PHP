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
     * @param array $values
     */
//    public function __construct(array $values)
//    {
//       // var_dump($values);
////        if (isset($values['name'])) {
////            $this->name = $values['name'];
////        }
////
////        if (isset($values['type'])) {
////            if ($values['type'] === 'DIRECT') {
////                $this->type = self::DIRECT;
////            } elseif ($values['type'] === 'SCHEDULED') {
////                $this->type = self::SCHEDULED;
////            } else {
////                throw new \UnexpectedValueException(sprintf("Value '%s' is not a valid type", $values['type']));
////            }
////        }
//    }

    /**
     * @return string
     */
    public function getTask(): string
    {
        return $this->task;
    }
}
