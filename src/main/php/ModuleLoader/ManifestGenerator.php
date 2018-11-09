<?php

declare(strict_types=1);
/**
 * Copyright 2018 Andrew O'Rourke
 */

namespace ModuleLoader;

/**
 * Class ModuleLoader
 * @package ModuleLoader
 *
 * Is responsible for handling the loading of modules
 */
class ManifestGenerator
{
    const MANIFEST_FILENAME = 'vendor/modules.php';
    const DISALLOWED_FOLDERS = ['.', '..', 'test', 'tests', 'logs'];

    /**
     * This method is meant to be invoked by Composer whenever the packages are
     * updated. It discovers and generates a manifest of all the modules.
     */
    static function generateManifest(): array
    {
        $modules = self::recursiveSearch('.');
        $categories = [];

        foreach ($modules as $module) {
            foreach ($module->getCategories() as $category) {
                $categories[$category->getName()][] = $module;
            }
        }

        return $categories;
    }

    static function dumpManifestToFile($filename = self::MANIFEST_FILENAME): void
    {
        $categories = self::generateManifest();

        $phpOutput = "<?php\n// modules.php @generated by ModuleLoader\n\n";
        $payload = serialize($categories);
        $phpOutput .= 'return unserialize(' . PHP_EOL . '    \'';
        $chunks = str_split($payload, 70);
        $phpOutput .= implode("' . " . PHP_EOL . "    '", $chunks);
        $phpOutput .= '\');' . PHP_EOL;

        file_put_contents(
            $filename,
            $phpOutput);
    }

    private static function recursiveSearch($folderName): array
    {
        $results = [];
        $dir = new \DirectoryIterator($folderName);
        foreach ($dir as $file) {
            if ($file->isFile() && $file->getExtension() == 'php') {
                $result = self::findModules($file->getPathname());
                if (!is_null($result)) {
                    $results[] = $result;
                }
            } elseif ($file->isDir()) {
                if (!in_array($file->getFilename(), self::DISALLOWED_FOLDERS)) {
                    $nested = self::recursiveSearch($file->getPathname());
                    $results = array_merge($results, $nested);
                }
            }
        }
        return $results;
    }

    private static function findModules($filePathname)
    {
        $matches = [];
        preg_match('/namespace (\w+);[^\/]+\/\*\*[^\/]*@module ([\w\d $()=]+)\n[^{]*\/\s+class\s+(\w+)/m',
            file_get_contents($filePathname),
            $matches);
        if (count($matches) > 0) {
            $module = new ModuleDefinition(
                $matches[1],
                self::parseCategories($matches[2]),
                $matches[3]
            );
            return $module;
        } else {
            return null;
        }
    }

    public static function parseCategories($categoryString): array
    {
        $entries = explode(' ', trim($categoryString));
        $categories = [];
        foreach ($entries as $entry) {
            $matches = [];
            preg_match('/(\w+)\((.*)\)/', $entry, $matches);
            if (count($matches) == 0) {
                $categories[] = new ModuleCategory($entry);
            } else {
                $variables = explode(',', $matches[2]);
                $parsedVariables = [];
                foreach ($variables as $variable) {
                    $components = explode('=', $variable);
                    if (count($components) == 1) {
                        $parsedVariables[] = trim($components[0]);
                    } else {
                        $parsedVariables[trim($components[0])] = trim($components[1]);
                    }
                }
                $categories[] = new ModuleCategory($matches[1],
                    $parsedVariables);
            }
        }
        return $categories;
    }
}