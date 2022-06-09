<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 *
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

declare(strict_types=1);

namespace Phpfastcache\Util;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

trait ClassNamespaceResolverTrait
{
    /**
     * @var array<string, string>
     */
    protected static array $namespaces = [];

    /**
     * Iterate over all files in the given directory searching for classes.
     *
     * NOTICE: This method has been borrowed from Symfony ClassLoader 3.4 since they
     * deprecated the whole component as of SF4. Our thanks to them.
     *
     * @param string $dir The directory to search in or an iterator
     *
     * @return array<string, string> A class map array
     */
    protected static function createClassMap(string $dir): array
    {
        $dirIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

        $map = [];

        foreach ($dirIterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = $file->getRealPath() ?: $file->getPathname();
            if ('php' !== pathinfo($path, PATHINFO_EXTENSION)) {
                continue;
            }
            $classes = self::findClasses($path);
            gc_mem_caches();
            foreach ($classes as $class) {
                $map[$class] = $path;
            }
        }

        return $map;
    }

    /**
     * Extract the classes in the given file.
     *
     * NOTICE: This method has been borrowed from Symfony ClassLoader 3.4 since they
     * deprecated the whole component as of SF4. Our thanks to them.
     *
     * @param string $path The file to check
     *
     * @return string[] The found classes
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected static function findClasses(string $path): array
    {
        $contents = file_get_contents($path);
        $tokens = token_get_all($contents);
        $classes = [];
        $namespace = '';
        for ($i = 0; isset($tokens[$i]); ++$i) {
            $token = $tokens[$i];
            if (!isset($token[1])) {
                continue;
            }
            $class = '';
            switch ($token[0]) {
                case T_NAMESPACE:
                    $namespace = self::buildTokenNamespace($i, $tokens);
                    break;
                case T_CLASS:
                case T_INTERFACE:
                case T_TRAIT:
                    $classes = self::buildTokenClasses($namespace, $class, $classes, $i, $tokens);
                    break;
                default:
                    break;
            }
        }

        return $classes;
    }

    /**
     * @param string $namespace
     * @param string $class
     * @param string[] $classes
     * @param int $index
     * @param array<array<mixed>|string> $tokens
     * @return string[]
     */
    protected static function buildTokenClasses(string $namespace, string $class, array $classes, int $index, array $tokens): array
    {
        // Skip usage of ::class constant
        $isClassConstant = false;
        for ($j = $index - 1; $j > 0; --$j) {
            if (!isset($tokens[$j][1])) {
                break;
            }
            if (T_DOUBLE_COLON === $tokens[$j][0]) {
                $isClassConstant = true;
                break;
            }

            if (!\in_array($tokens[$j][0], [T_WHITESPACE, T_DOC_COMMENT, T_COMMENT], false)) {
                break;
            }
        }
        if ($isClassConstant) {
            return $classes;
        }

        // Find the classname
        while (isset($tokens[++$index][1])) {
            $t = $tokens[$index];
            if (T_STRING === $t[0]) {
                $class .= $t[1];
            } elseif ('' !== $class && T_WHITESPACE === $t[0]) {
                break;
            }
        }

        return \array_merge($classes, [\ltrim($namespace . $class, '\\')]);
    }

    /**
     * @param int $index
     * @param array<array<mixed>|string> $tokens
     * @return string
     */
    protected static function buildTokenNamespace(int $index, array $tokens): string
    {
        $namespace = '';

        // If there is a namespace, extract it (PHP 8 test)
        if (\defined('T_NAME_QUALIFIED')) {
            while (isset($tokens[++$index][1])) {
                if ($tokens[$index][0] === T_NAME_QUALIFIED) {
                    $namespace = $tokens[$index][1];
                    break;
                }
            }
        } else {
            while (isset($tokens[++$index][1])) {
                if (\in_array($tokens[$index][0], [T_STRING, T_NS_SEPARATOR], true)) {
                    $namespace .= $tokens[$index][1];
                }
            }
        }

        return $namespace . '\\';
    }

    /**
     * @return string
     */
    public static function getClassNamespace(): string
    {
        if (!isset(self::$namespaces[static::class])) {
            self::$namespaces[static::class] = substr(static::class, 0, strrpos(static::class, '\\'));
        }

        return self::$namespaces[static::class];
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return static::class;
    }
}
