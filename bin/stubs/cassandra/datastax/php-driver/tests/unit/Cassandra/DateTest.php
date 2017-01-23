<?php

/**
 * Copyright 2015-2016 DataStax, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Cassandra;

/**
 * @requires extension cassandra
 */
class DateTest extends \PHPUnit_Framework_TestCase
{
    const SECONDS_PER_DAY = 86400;

    public function testConstruct()
    {
        $date = new Date(0);
        $this->assertEquals($date->seconds(), 0);

        $date = new Date(1);
        $this->assertEquals($date->seconds(), 0); // Truncated

        $date = new Date(self::SECONDS_PER_DAY);
        $this->assertEquals($date->seconds(), self::SECONDS_PER_DAY);
    }

    public function testConstructNow()
    {
        $date = new Date();
        $this->assertEquals($date->seconds(), (int) (time() / self::SECONDS_PER_DAY) * self::SECONDS_PER_DAY, "", 1);
    }

    public function testFromDateTime()
    {
        // Epoch
        $datetime = new \DateTime("1970-01-01T00:00:00+0000");
        $date = Date::fromDateTime($datetime);
        $this->assertEquals($date->seconds(), 0);
        $this->assertEquals($date->toDateTime(), $datetime);

        // Epoch + 1
        $datetime = new \DateTime("1970-01-02T00:00:00+0000");
        $date = Date::fromDateTime($datetime);
        $this->assertEquals($date->seconds(), self::SECONDS_PER_DAY);
        $this->assertEquals($date->toDateTime(), $datetime);

        // Epoch - 1 (should work if cpp-driver >= 2.4.2, otherwise it's broken)
        if (version_compare(\Cassandra::CPP_DRIVER_VERSION, "2.4.2") >= 0) {
          $date = Date::fromDateTime(new \DateTime("1969-12-31T00:00:00"));
          $this->assertEquals($date->seconds(), -1 * self::SECONDS_PER_DAY);
        }
    }

    public function testToDateTimeWithTime()
    {
        // Epoch
        $datetime = new \DateTime("1970-01-01T00:00:01+0000");
        $date = Date::fromDateTime($datetime);
        $this->assertEquals($date->seconds(), 0);
        $this->assertEquals($date->toDateTime(new Time(1000 * 1000 * 1000)), $datetime);
    }

    public function testType() {
        $date = new Date(0) ;
        $this->assertEquals($date->type(),  Type::date());
    }
}
