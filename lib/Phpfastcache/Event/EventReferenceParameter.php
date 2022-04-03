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

namespace Phpfastcache\Event;

use Phpfastcache\Exceptions\PhpfastcacheInvalidTypeException;

class EventReferenceParameter
{
    public function __construct(protected mixed &$parameter, protected bool $allowTypeChange = false)
    {
    }

    public function getParameterValue(): mixed
    {
        return $this->parameter;
    }

    /**
     * @throws PhpfastcacheInvalidTypeException
     */
    public function setParameterValue(mixed $newValue): void
    {
        if (!$this->allowTypeChange) {
            $currentType = \gettype($this->parameter);
            $newType = \gettype($newValue);
            if ($newType !== $currentType) {
                throw new PhpfastcacheInvalidTypeException(\sprintf(
                    'You tried to change the variable type from "%s" to "%s" which is not allowed.',
                    $currentType,
                    $newType
                ));
            }
        }

        $this->parameter = $newValue;
    }

    public function __invoke(): mixed
    {
        return $this->getParameterValue();
    }
}
