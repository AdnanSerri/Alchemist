<?php

namespace Serri\Alchemist\Support;

use Serri\Alchemist\Exceptions\InvalidConfigurationException;

/**
 * @internal
 */
final class BrewingConfigLoader
{
    /**
     * @return array{formulas_folder_path: string, ingredients: array<int, class-string>}
     *
     * @throws InvalidConfigurationException
     */
    public static function load(): array
    {
        $config = config('alchemist');

        if (! is_array($config)) {
            throw new InvalidConfigurationException(
                "The 'alchemist' configuration is missing. Publish it with: php artisan vendor:publish --tag=alchemist-config"
            );
        }

        if (empty($config['formulas_folder_path']) || ! is_string($config['formulas_folder_path'])) {
            throw new InvalidConfigurationException(
                "The 'alchemist.formulas_folder_path' configuration key must be set to the Formulas folder path."
            );
        }

        if (empty($config['ingredients']) || ! is_array($config['ingredients'])) {
            throw new InvalidConfigurationException(
                "The 'alchemist.ingredients' configuration key must be a non-empty array of ingredient classes."
            );
        }

        return $config;
    }
}
