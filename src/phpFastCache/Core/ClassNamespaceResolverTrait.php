<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */

namespace phpFastCache\Core;

/**
 * Trait ClassNamespaceResolverTrait
 * @package phpFastCache\Core
 */
trait ClassNamespaceResolverTrait
{
    /**
     * @return string
     */
    protected function getClassNamespace()
    {
        static $namespace;

        if (!$namespace) {
            $namespace = (new \ReflectionObject($this))->getNamespaceName();
        }

        return $namespace;
    }
}