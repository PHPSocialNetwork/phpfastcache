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

trait EventManagerDispatcherTrait
{
    /**
     * @var EventManagerInterface
     */
    protected EventManagerInterface $eventManager;
/**
     * @return EventManagerInterface
     */
    public function getEventManager(): EventManagerInterface
    {
        return $this->eventManager;
    }

    /**
     * @param EventManagerInterface $em
     * @return static
     */
    public function setEventManager(EventManagerInterface $em): static
    {
        $this->eventManager = $em;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasEventManager(): bool
    {
        return isset($this->eventManager);
    }
}
