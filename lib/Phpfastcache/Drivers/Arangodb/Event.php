<?php

namespace Phpfastcache\Drivers\Arangodb;

class Event extends \Phpfastcache\Event\Event
{
    public const ARANGODB_CONNECTION = 'ArangodbConnection';
    public const ARANGODB_COLLECTION_PARAMS = 'ArangodbCollectionParams';
}
