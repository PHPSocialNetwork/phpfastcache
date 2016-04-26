<?php
/**
 * Created by PhpStorm.
 * User: Geolim4
 * Date: 09/04/2016
 * Time: 01:45
 */

namespace phpFastCache\Core;


class CacheItemPrototype extends \stdClass
{
    /**
     * @var mixed
     */
    public $data;

    /**
     * @var \DateTimeInterface
     */
    public $expirationDate;

    /**
     * @var array
     */
    public $tags = [];

    public function __construct(\DateTimeInterface $expirationDate = null)
    {
        $this->expirationDate = ($expirationDate ?: new \DateTime());
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return $this->expirationDate->getTimestamp() >= (new \DateTime())->getTimestamp();
    }
}