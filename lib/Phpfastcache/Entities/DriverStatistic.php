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
declare(strict_types=1);

namespace Phpfastcache\Entities;

use Phpfastcache\Exceptions\phpFastCacheInvalidArgumentTypeException;

/**
 * Class DriverStatistic
 * @package phpFastCache\Entities
 */
class DriverStatistic
{
    /**
     * @var string
     */
    protected $info = '';

    /**
     * @var int
     */
    protected $size = 0;

    /**
     * @var string
     */
    protected $data = '';

    /**
     * @var mixed
     */
    protected $rawData;

    /**
     * @return string Return info or false if no information available
     */
    public function getInfo(): string
    {
        return $this->info;
    }

    /**
     * @return int Return size in octet or false if no information available
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $info
     * @return $this
     * @throws phpFastCacheInvalidArgumentTypeException
     */
    public function setInfo($info)
    {
        if (\is_string($info)) {
            $this->info = ($info ?: '');
        } else {
            throw new phpFastCacheInvalidArgumentTypeException('string', $info);
        }
        return $this;
    }


    /**
     * @param int $size
     * @return $this
     * @throws phpFastCacheInvalidArgumentTypeException
     */
    public function setSize($size)
    {
        if (\is_int($size)) {
            $this->size = ($size ?: 0);
        } else {
            throw new phpFastCacheInvalidArgumentTypeException('int', $size);
        }

        return $this;
    }

    /**
     * @param mixed $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = ($data ?: '');

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     * @param mixed $raw
     * @return $this
     */
    public function setRawData($raw)
    {
        $this->rawData = $raw;

        return $this;
    }

    /**
     * @return array
     */
    public function getPublicDesc(): array
    {
        return [
          'Info' => 'Cache Information',
          'Size' => 'Cache Size',
          'Data' => 'Cache items keys',
          'RawData' => 'Cache raw data',
        ];
    }
}