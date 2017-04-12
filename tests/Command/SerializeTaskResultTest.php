<?php
declare(strict_types=1);

namespace Command;

use Attlaz\Core\Command\DeserializeTaskResult;
use Attlaz\Core\Command\Job\DownloadFile;
use Attlaz\Core\Command\SerializeTaskResult;
use Attlaz\Core\Model\Task;
use Attlaz\Core\Model\TaskResult;
use PHPUnit\Framework\TestCase;

class SerializeTaskResultTest extends TestCase
{

    public function testSerialization()
    {
        $task = new Task('method', []);
        $data = 'DATA';
        $time = new \DateTime('2017-08-11 21:58:58.512276');
        $taskResult = new TaskResult($task, $data);
        $taskResult->setReceived($time);
        $taskResult->setResponded($time);

        $cmd = new SerializeTaskResult();
        $serialized = $cmd->__invoke($taskResult);

        $this->assertEquals('{"task":{"method":"method","arguments":[]},"data":"DATA","success":true,"received":{"date":"2017-08-11 21:58:58.512276","timezone_type":3,"timezone":"UTC"},"responded":{"date":"2017-08-11 21:58:58.512276","timezone_type":3,"timezone":"UTC"}}', $serialized);

    }

    public function testSerialization_Error()
    {
        $task = new Task('method', []);

        $cmd = new DownloadFile();
        $data = $cmd->__invoke('http://www.uniwallpaper.com/static/cache/4f/cc/4fcc465449f55ddc995d0738a852d64a.jpg');
        $time = new \DateTime('2017-08-11 21:58:58.512276');
        $taskResult = new TaskResult($task, $data);
        $taskResult->setReceived($time);
        $taskResult->setResponded($time);

        $cmd = new SerializeTaskResult();
        $serialized = $cmd->__invoke($taskResult);

        //$this->assertEquals('{"task":{"method":"method","arguments":[]},"data":"DATA","success":true,"received":{"date":"2017-08-11 21:58:58.512276","timezone_type":3,"timezone":"UTC"},"responded":{"date":"2017-08-11 21:58:58.512276","timezone_type":3,"timezone":"UTC"}}', $serialized);

    }

    public function testDeserialization()
    {
        $serializedTaskResult = '{"task":{"method":"method","arguments":[]},"data":"DATA","success":true,"received":{"date":"2017-08-11 21:58:58.512276","timezone_type":3,"timezone":"UTC"},"responded":{"date":"2017-08-11 21:58:58.512276","timezone_type":3,"timezone":"UTC"}}';
        $time = new \DateTime('2017-08-11 21:58:58.512276');

        $cmd = new DeserializeTaskResult();
        $taskResult = $cmd->__invoke($serializedTaskResult);

        $this->assertInstanceOf(Task::class, $taskResult->getTask());

        $this->assertEquals($time, $taskResult->getReceived());
        $this->assertEquals($time, $taskResult->getResponded());

    }
}