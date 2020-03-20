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

namespace Phpfastcache\Entities;

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
     * @param string $info
     * @return $this
     */
    public function setInfo(string $info): self
    {
        $this->info = $info;

        return $this;
    }

    /**
     * @return int Return size in octet or false if no information available
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @param int $size
     * @return $this
     */
    public function setSize(int $size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     * @return $this
     */
    public function setData($data): self
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
    public function setRawData($raw): self
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