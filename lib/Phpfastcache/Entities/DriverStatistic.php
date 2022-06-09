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

class DriverStatistic
{
    protected string $info = '';

    protected int $size = 0;

    protected string $data = '';

    protected mixed $rawData;

    public function getInfo(): string
    {
        return $this->info;
    }

    public function setInfo(string $info): static
    {
        $this->info = $info;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): static
    {
        $this->data = ($data ?: '');

        return $this;
    }

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
            'Data' => 'Cache items keys',
            'RawData' => 'Cache raw data',
        ];
    }
}
