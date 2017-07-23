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

namespace phpFastCache\Entities;

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
     * @var string
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
     * @return string|bool Return infos or false if no information available
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @return int|bool Return size in octet or false if no information available
     */
    public function getSize()
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
     */
    public function setInfo($info)
    {
        $this->info = ($info ?: '');

        return $this;
    }


    /**
     * @param int $size
     * @return $this
     */
    public function setSize($size)
    {
        $this->size = ($size ?: 0);

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
    public function getPublicDesc()
    {
        return [
          'Info' => 'Cache Information',
          'Size' => 'Cache Size',
          'Data' => 'Cache items keys',
          'RawData' => 'Cache raw data',
        ];
    }
}