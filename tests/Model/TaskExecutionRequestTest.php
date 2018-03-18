<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

use PHPUnit\Framework\TestCase;

class TaskExecutionRequestTest extends TestCase
{
    public function testFromArray()
    {
        $strTask = 'eyJpZCI6IkFDNTgwM0QxMCIsInRhc2siOiJjaGFvcyIsInRyaWdnZXIiOiJhcGkiLCJ0aW1lIjoiMjAxOC0wMy0xOFQyMToyODowNS43ODZaIiwiYXJndW1lbnRzIjp7Iml0ZXJhdGlvbiI6NX19';

        $strTask = base64_decode($strTask);
        $taskArray = \json_decode($strTask, true);

        $result = TaskExecutionRequest::fromArray($taskArray);

        $expected = new TaskExecutionRequest('chaos', 'AC5803D10');
        $expected->setArguments(['iteration' => 5]);
        $this->assertEquals($expected, $result);
    }

}