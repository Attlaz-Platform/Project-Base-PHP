<?php
declare(strict_types=1);

namespace Helper;

use Attlaz\Core\Command\DeserializeTaskResult;
use Attlaz\Core\Command\SerializeTaskResult;
use Attlaz\Core\Helper\DateTimeHelper;
use Attlaz\Core\Model\Task;
use Attlaz\Core\Model\TaskResult;
use PHPUnit\Framework\TestCase;

class DateTimeHelperTest extends TestCase
{
    public function testSerialization()
    {
        $now = new \DateTime();

        $serialized = json_encode($now);

        $deserialized = json_decode($serialized, true);

        $dateTime = DateTimeHelper::deserialize($deserialized);
        $this->assertEquals($now, $dateTime);

        $this->assertEquals($now->getTimezone(), $dateTime->getTimezone());
        $this->assertEquals($now->getTimestamp(), $dateTime->getTimestamp());

    }

}