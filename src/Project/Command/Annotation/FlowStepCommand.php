<?php

declare(strict_types=1);

namespace Attlaz\Project\Command\Annotation;

/**
 * FlowStepCommand
 *
 * Marks a class as a step flow command
 *
 * @author Stijn Duynslaeger <stijn@attlaz.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS)] final class FlowStepCommand
{
    /**
     * Id of the flow step
     */
    public string $flowStepId;
    public array $connections;

    public function __construct(string $flowStepId, array $connections = [])
    {
        $this->flowStepId = $flowStepId;
        $this->connections = $connections;
    }

    public function getFlowStepId(): string
    {
        return $this->flowStepId;
    }
}
