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
class DriverIO
{
    /**
     * @var int
     */
    protected $writeHit = 0;

    /**
     * @var int
     */
    protected $readHit = 0;

    /**
     * @var int
     */
    protected $readMiss = 0;

    /**
     * @return int
     */
    public function getWriteHit(): int
    {
        return $this->writeHit;
    }

    /**
     * @param int $writeHit
     * @return DriverIO
     */
    public function setWriteHit(int $writeHit): DriverIO
    {
        $this->writeHit = $writeHit;
        return $this;
    }

    /**
     * @return DriverIO
     */
    public function incWriteHit(): DriverIO
    {
        $this->writeHit++;
        return $this;
    }

    /**
     * @return int
     */
    public function getReadHit(): int
    {
        return $this->readHit;
    }

    /**
     * @param int $readHit
     * @return DriverIO
     */
    public function setReadHit(int $readHit): DriverIO
    {
        $this->readHit = $readHit;
        return $this;
    }

    /**
     * @return DriverIO
     */
    public function incReadHit(): DriverIO
    {
        $this->readHit++;
        return $this;
    }

    /**
     * @return int
     */
    public function getReadMiss(): int
    {
        return $this->readMiss;
    }

    /**
     * @param int $readMiss
     * @return DriverIO
     */
    public function setReadMiss(int $readMiss): DriverIO
    {
        $this->readMiss = $readMiss;
        return $this;
    }

    /**
     * @return DriverIO
     */
    public function incReadMiss(): DriverIO
    {
        $this->readMiss++;
        return $this;
    }
}