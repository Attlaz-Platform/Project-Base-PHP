<?php
declare(strict_types=1);

namespace Attlaz\Project\Connections;


class AdapterConnectionDefinition
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getKey(): string
    {
        return $this->data['key'];
    }

    public function getAdapterName(): string
    {
        return $this->data['adapter'];
    }

    /**
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    public function getConfiguration(string $key, $default = null): ?string
    {
        $configurations = $this->data['configuration'];
        foreach ($configurations as $configuration) {
            if ($configuration['key'] === $key) {
                return $configuration['value'];
            }
        }

        return $default;
    }
}
