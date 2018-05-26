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
namespace Phpfastcache\Config;


interface ConfigurationOptionInterface
{
    /**
     * @param $args
     * ArrayObject constructor.
     */
    public function __construct(...$args);

    /**
     * @param string $optionName
     * @return mixed|null
     * @deprecated Use ->getOptionName() instead
     */
    public function getOption(string $optionName);

    /**
     * @param string $optionName
     * @return mixed|null
     */
    public function isValidOption(string $optionName);
}