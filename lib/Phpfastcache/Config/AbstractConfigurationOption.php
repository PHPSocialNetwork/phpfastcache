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

namespace Phpfastcache\Config;

use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

abstract class AbstractConfigurationOption implements LockableConfigurationInterface
{
    private bool $lockedObject = false;
    private ExtendedCacheItemPoolInterface $locker;

    protected function getClassName(): string
    {
        return $this::class;
    }

    public function lock(ExtendedCacheItemPoolInterface $poolInstance): static
    {
        $this->lockedObject = true;
        $this->locker = $poolInstance;
        return $this;
    }

    public function lockedBy(): ExtendedCacheItemPoolInterface
    {
        return $this->locker;
    }

    public function isLocked(): bool
    {
        return $this->lockedObject;
    }

    /**
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function setProperty(string $propertyName, mixed $propertyValue): static
    {
        $this->enforceLockedProperty($propertyName);
        if (property_exists($this, $propertyName)) {
            $this->$propertyName = $propertyValue;
            return $this;
        }
        throw new PhpfastcacheInvalidArgumentException("Unknown property $propertyName in {$this->getClassName()} context.");
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    protected function enforceLockedProperty(string $propertyName): void
    {
        if ($this->lockedObject === true) {
            $dbt = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $cause = $dbt[\array_key_last($dbt)] ?? null;
            if ($cause) {
                $moreInfo = \sprintf('Caused line %d in %s', $cause['line'], $cause['file']);
            }

            throw new PhpfastcacheLogicException(\sprintf(
                'You can no longer change the configuration "%s" as the cache pool instance is now running. %s',
                $propertyName,
                $moreInfo ?? ''
            ));
        }
    }
}
