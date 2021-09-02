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

use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;

class EventReferenceParameter
{
    protected mixed $parameter;

    public function __construct(
        mixed &$parameter,
        protected bool $allowTypeChange = false
    ) {
        $this->parameter = &$parameter;
    }

    public function getParameterValue(): mixed
    {
        return $this->parameter;
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function setParameterValue(mixed $newValue): void
    {
        $currentType = \gettype($this->parameter);
        $newType = \gettype($newValue);

        if(!$this->allowTypeChange && $newType !== $currentType){
            throw new PhpfastcacheInvalidArgumentException(
                \sprintf('You tried to change the variable type from "%s" to "%s" which is not allowed.', $currentType, $newType)
            );
        }

        $this->parameter = $newValue;
    }

    public function __invoke(): mixed
    {
        return $this->getParameterValue();
    }
}
