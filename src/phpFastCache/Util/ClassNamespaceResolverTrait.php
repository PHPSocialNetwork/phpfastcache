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

namespace phpFastCache\Util;

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
     * @return string
     */
    protected function getClassNamespace()
    {
        if (!$this->namespace) {
            $this->namespace = substr(static::class, 0, strrpos(static::class, '\\'));
        }

        return $this->namespace;
    }
}