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

namespace Cassandra\Type;

use Cassandra\Type;

/**
 * @requires extension cassandra
 */
class ScalarTest extends \PHPUnit_Framework_TestCase
{
    public function testAllowCreatingTypes()
    {
        $this->assertEquals("some string", Type::varchar()->create("some string"));
    }

    /**
     * @dataProvider scalarTypes
     */
    public function testCompareEquals($type) {
        $this->assertTrue($type() == $type());
    }

    public function testCompareNotEquals() {
        $this->assertTrue(Type::ascii() != Type::bigint());
    }

    public function scalarTypes()
    {
        return array(
            array(function () { return Type::ascii();     }),
            array(function () { return Type::bigint();    }),
            array(function () { return Type::blob();      }),
            array(function () { return Type::boolean();   }),
            array(function () { return Type::counter();   }),
            array(function () { return Type::decimal();   }),
            array(function () { return Type::double();    }),
            array(function () { return Type::float();     }),
            array(function () { return Type::inet();      }),
            array(function () { return Type::int();       }),
            array(function () { return Type::text();      }),
            array(function () { return Type::timestamp(); }),
            array(function () { return Type::timeuuid();  }),
            array(function () { return Type::uuid();      }),
            array(function () { return Type::varchar();   }),
            array(function () { return Type::varint();    })
        );
    }
}
