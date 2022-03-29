<?php
declare(strict_types=1);


namespace Attlaz\Project\Connections;


class AdapterRegistrar
{
    private static array $adapters = [];

    public static function register(string $adapterId, string $path, string $factoryClassName): void
    {
        $formattedAdapterId = self::formatAdapterId($adapterId);
        if (isset(self::$adapters[$formattedAdapterId])) {
            throw new \LogicException($adapterId . '\' from \'' . $path . '\' '
                . 'has been already defined in \'' . self::$adapters[$formattedAdapterId] . '\'.'
            );
        } else {
            self::$adapters[$formattedAdapterId] = ['path' => str_replace('\\', '/', $path), 'factory' => $factoryClassName];
        }
    }

    public static function getFactoryClassName(string $adapterId): ?string
    {
        // TODO: class should be instance of AdapterFactory
        $formattedAdapterId = self::formatAdapterId($adapterId);
        if (isset(self::$adapters[$formattedAdapterId])) {
            return self::$adapters[$formattedAdapterId]['factory'];
        }
        return null;
    }

    public static function getAdapterNames(): array
    {
        return \array_keys(self::$adapters);
    }

    private static function formatAdapterId(string $adapterId): string
    {
        return \strtolower($adapterId);
    }

}
