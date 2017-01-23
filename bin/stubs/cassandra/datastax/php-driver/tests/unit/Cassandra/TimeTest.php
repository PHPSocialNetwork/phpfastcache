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
class TimeTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $time = new Time(0);
        $this->assertEquals((string)$time, "0");

        $time = new Time(42);
        $this->assertEquals((string)$time, "42");

        $time = new Time("86399999999999");
        $this->assertEquals((string)$time, "86399999999999");
    }

    public function testConstructNow()
    {
        $time = new Time();
        $this->assertEquals($time->seconds(), time() % (24 * 60 * 60), "", 1);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage nanoseconds must be nanoseconds since midnight, -1 given
     */
    public function testConstructNegative()
    {
        $time = new Time(-1);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage nanoseconds must be nanoseconds since midnight, '86400000000000' given
     */
    public function testConstructTooBig()
    {
        $time = new Time("86400000000000");
    }

    public function testFromDateTime()
    {
        $datetime = new \DateTime("1970-01-01T00:00:00+0000");
        $time = Time::fromDateTime($datetime);
        $this->assertEquals((string)$time, "0");

        $datetime = new \DateTime("1970-01-01T00:00:01+0000");
        $time = Time::fromDateTime($datetime);
        $this->assertEquals((string)$time, "1000000000");

        $datetime = new \DateTime("1970-01-01T23:59:59+0000");
        $time = Time::fromDateTime($datetime);
        $this->assertEquals((string)$time, "86399000000000");
    }

    public function testType() {
        $time = new Time(0) ;
        $this->assertEquals($time->type(),  Type::time());
    }
}
