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

namespace Phpfastcache\Event;

/**
 * Interface EventInterface
 * @package Phpfastcache\Event
 */
trait EventManagerDispatcherTrait
{
    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * @return EventManagerInterface
     */
    public function getEventManager(): EventManagerInterface
    {
        return $this->eventManager;
    }

    /**
     * @param EventManagerInterface $em
     * @return EventManagerDispatcherInterface
     */
    public function setEventManager(EventManagerInterface $em): EventManagerDispatcherInterface
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