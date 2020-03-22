<?php

/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Util;

use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Traversable;


/**
 * Trait ClassNamespaceResolverTrait
 * @package phpFastCache\Core
 */
trait ClassNamespaceResolverTrait
{
    /**
     * @var string
     */
    protected $namespace;

    /**
     * Iterate over all files in the given directory searching for classes.
     *
     * NOTICE: This method has been borrowed from Symfony ClassLoader 3.4 since they
     * deprecated the whole component as of SF4. Our thanks to them.
     *
     * @param Iterator|string|array $dir The directory to search in or an iterator
     *
     * @return array A class map array
     */
    protected static function createClassMap($dir): array
    {
        if (is_string($dir)) {
            $dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        }
        $map = [];

        if (is_array($dir) || $dir instanceof Traversable) {
            foreach ($dir as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                $path = $file->getRealPath() ?: $file->getPathname();
                if ('php' !== pathinfo($path, PATHINFO_EXTENSION)) {
                    continue;
                }
                $classes = self::findClasses($path);
                if (PHP_VERSION_ID >= 70000) {
                    // PHP 7 memory manager will not release after token_get_all(), see https://bugs.php.net/70098
                    gc_mem_caches();
                }
                foreach ($classes as $class) {
                    $map[$class] = $path;
                }
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
     * @return array The found classes
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
                    $namespace = '';
                    // If there is a namespace, extract it
                    while (isset($tokens[++$i][1])) {
                        if (in_array($tokens[$i][0], [T_STRING, T_NS_SEPARATOR])) {
                            $namespace .= $tokens[$i][1];
                        }
                    }
                    $namespace .= '\\';
                    break;
                case T_CLASS:
                case T_INTERFACE:
                case T_TRAIT:
                    // Skip usage of ::class constant
                    $isClassConstant = false;
                    for ($j = $i - 1; $j > 0; --$j) {
                        if (!isset($tokens[$j][1])) {
                            break;
                        }
                        if (T_DOUBLE_COLON === $tokens[$j][0]) {
                            $isClassConstant = true;
                            break;
                        } elseif (!in_array($tokens[$j][0], [T_WHITESPACE, T_DOC_COMMENT, T_COMMENT], false)) {
                            break;
                        }
                    }
                    if ($isClassConstant) {
                        break;
                    }
                    // Find the classname
                    while (isset($tokens[++$i][1])) {
                        $t = $tokens[$i];
                        if (T_STRING === $t[0]) {
                            $class .= $t[1];
                        } elseif ('' !== $class && T_WHITESPACE === $t[0]) {
                            break;
                        }
                    }
                    $classes[] = ltrim($namespace . $class, '\\');
                    break;
                default:
                    break;
            }
        }
        return $classes;
    }

    /**
     * @return string
     */
    public function getClassNamespace(): string
    {
        if (!$this->namespace) {
            $this->namespace = substr(static::class, 0, strrpos(static::class, '\\'));
        }

        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return static::class;
    }
}
