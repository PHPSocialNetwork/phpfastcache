<?php

/**
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */
declare(strict_types=1);

namespace Phpfastcache\Entities;

class DriverIO
{
    protected int $writeHit = 0;

    protected int $readHit = 0;

    protected int $readMiss = 0;

    public function getWriteHit(): int
    {
        return $this->writeHit;
    }

    public function setWriteHit(int $writeHit): self
    {
        $this->writeHit = $writeHit;

        return $this;
    }

    public function incWriteHit(): self
    {
        ++$this->writeHit;

        return $this;
    }

    public function getReadHit(): int
    {
        return $this->readHit;
    }

    public function setReadHit(int $readHit): self
    {
        $this->readHit = $readHit;

        return $this;
    }

    public function incReadHit(): self
    {
        ++$this->readHit;

        return $this;
    }

    public function getReadMiss(): int
    {
        return $this->readMiss;
    }

    public function setReadMiss(int $readMiss): self
    {
        $this->readMiss = $readMiss;

        return $this;
    }

    public function incReadMiss(): self
    {
        ++$this->readMiss;

        return $this;
    }
}
