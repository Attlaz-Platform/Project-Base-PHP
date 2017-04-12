<?php
declare(strict_types=1);

namespace Command;

use Attlaz\Core\Command\ExecuteTask;
use Attlaz\Core\Model\Task;
use PHPUnit\Framework\TestCase;

class ExecuteTaskTest extends TestCase
{

    public function testUnknownJob()
    {
        $task = new Task('nonExistingJob');

        $executeTaskCommand = new ExecuteTask();
        $result = $executeTaskCommand->__invoke($task);
        $this->assertFalse($result->getSuccess());
        $this->assertEquals('Unknown method "nonExistingJob"', $result->getData());

    }

    public function testDummyJob()
    {
        $task = new Task('ping', ['input' => 'Hello world!']);

        $executeTaskCommand = new ExecuteTask();
        $result = $executeTaskCommand->__invoke($task);
        $this->assertTrue($result->getSuccess());
        $this->assertEquals('Pong [Hello world!]', $result->getData());
    }
    //TODO: test optional argument
    //TODO: test non existing argument

}