<?php
declare(strict_types=1);


namespace Attlaz\Project\Connections;


class AdapterRegistrar
{
    private static $adapters = [];

    public static function register(string $adapterName, string $path, string $factoryClassName): void
    {
        $formattedAdapterName = self::formatAdapterName($adapterName);
        if (isset(self::$adapters[$formattedAdapterName])) {
            throw new \LogicException($adapterName . '\' from \'' . $path . '\' '
                . 'has been already defined in \'' . self::$adapters[$formattedAdapterName] . '\'.'
            );
        } else {
            self::$adapters[$formattedAdapterName] = ['path' => str_replace('\\', '/', $path), 'factory' => $factoryClassName];
        }
    }

    public static function getFactoryClassName(string $adapterName): ?string
    {
        // TODO: class should be instance of AdapterFactory
        $formattedAdapterName = self::formatAdapterName($adapterName);
        if (isset(self::$adapters[$formattedAdapterName])) {
            return self::$adapters[$formattedAdapterName]['factory'];
        }
        return null;
    }

    public static function getAdapterNames(): array
    {
        return \array_keys(self::$adapters);
    }

    private static function formatAdapterName(string $adapterName): string
    {
        return \strtolower($adapterName);
    }

}
