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

namespace Phpfastcache\Entities;

/**
 * @see https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV5%CB%96%5D-The-cache-statistics
 */
class DriverStatistic
{
    protected string $info = '';

    protected ?int $size = 0;

    protected ?int $count = 0;

    protected string $data = '';

    protected mixed $rawData;

    /**
     * Return quick information about the driver instance
     * @return string
     */
    public function getInfo(): string
    {
        return $this->info;
    }

    public function setInfo(string $info): static
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Return the approximate size taken by the driver instance (in bytes) (null if unsupported by the driver)
     * @return int|null
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Return the approximate count of elements stored in a driver database (or collection if applicable). Added in v9.2.3
     * @since 9.2.3
     * @return int|null
     */
    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): static
    {
        $this->count = $count;
        return $this;
    }

    /**
     * Return an array of item keys used by this driver instance (deprecated as of v9.2.3, will be removed as of v10)
     * @deprecated as of phpfastcache 9.2.3, will be removed as of v10
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @deprecated as of phpfastcache 9.2.3, will be removed as of v10
     */
    public function setData(string $data): static
    {
        $this->data = ($data ?: '');

        return $this;
    }

    /**
     * Return a bunch of random data provided by the driver. Any type can be provided, usually an array
     * @return mixed
     */
    public function getRawData(): mixed
    {
        return $this->rawData;
    }

    public function setRawData(mixed $raw): static
    {
        $this->rawData = $raw;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getPublicDesc(): array
    {
        return [
            'Info' => 'Cache Information',
            'Size' => 'Cache Size',
            'Count' => 'Cache database/collection count',
            'Data' => 'Cache items keys (Deprecated)',
            'RawData' => 'Cache raw data',
        ];
    }
}
