<?php

namespace Serri\Alchemist\Support;

use Exception;
use Illuminate\Support\Facades\File;

/**
 * @internal
 */
final class BrewingConfigLoader
{
    /**
     * @throws Exception
     */
    public static function load(): array
    {
        $config = config('alchemist');

        if (! array_key_exists('formulas_folder_path', $config) or $config['formulas_folder_path'] == '' or is_null($config['formulas_folder_path'])) {
            throw new Exception('formulas_folder_path key should exist with a value of the Formulas folder path.');
        }

        if (! File::exists($config['formulas_folder_path']) and File::isDirectory($config['formulas_folder_path'])) {
            throw new Exception("Formula folder is not found at '{$config['formulas_folder_path']}'.");
        }

        if (! array_key_exists('ingredients', $config) or $config['ingredients'] == '' or is_null($config['ingredients']) or ! is_array($config['ingredients'])) {
            throw new Exception('ingredients key should exist with a value of the ingredients array.');
        }

        return $config;
    }
}
