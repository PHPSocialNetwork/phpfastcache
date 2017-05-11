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
class FloatTest extends \PHPUnit_Framework_TestCase
{
    const EPSILON = 0.00001;

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid float value: ''
     */
    public function testThrowsWhenCreatingFromEmpty()
    {
        new Float("");
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid float value: 'invalid'
     */
    public function testThrowsWhenCreatingFromInvalid()
    {
        new Float("invalid");
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid characters were found in value: '123.123    '
     */
    public function testThrowsWhenCreatingFromInvalidTrailingChars()
    {
        new Float("123.123    ");
    }

    /**
     * @dataProvider      outOfRangeStrings
     * @expectedException RangeException
     */
    public function testThrowsWhenCreatingOutOfRange($string)
    {
        new Float($string);
    }

    public function outOfRangeStrings()
    {
        return array(
            array("10e308"),
            array("-10e308"),
        );
    }

    /**
     * @dataProvider validStrings
     */
    public function testCorrectlyParsesStrings($number, $expected)
    {
        $number = new Float($number);
        $this->assertEquals((float)$number, (float)$expected, "", self::EPSILON);
    }

    public function validStrings()
    {
        return array(
            array("123", 123),
            array("123.123", 123.123),
            array(".123", 0.123),
            array("0.123", 0.123),
            array("1e10", 1e10),
        );
    }

    /**
     * @dataProvider validNumbers
     */
    public function testFromNumbers($number)
    {
        $float = new Float($number);
        $this->assertEquals((float)$number, (float)$float, "", self::EPSILON);
        $this->assertEquals((int)$number, $float->toInt());
        $this->assertEquals((float)$number, (float)(string)$float, "", self::EPSILON);
    }

    public function validNumbers()
    {
        return array(
            array(0.123),
            array(123),
        );
    }

    public function testAdd()
    {
        $float1 = new Float("1");
        $float2 = new Float("0.5");
        $this->assertEquals(1.5, (float)$float1->add($float2), "", self::EPSILON);
    }

    public function testSub()
    {
        $float1 = new Float("1");
        $float2 = new Float("0.5");
        $this->assertEquals(0.5, (float)$float1->sub($float2), "", self::EPSILON);
    }

    public function testMul()
    {
        $float1 = new Float("2");
        $float2 = new Float("0.5");
        $this->assertEquals(1.0, (float)$float1->mul($float2), "", self::EPSILON);
    }

    public function testDiv()
    {
        $float1 = new Float("1");
        $float2 = new Float("0.5");
        $this->assertEquals(2, (float)$float1->div($float2), "", self::EPSILON);
    }

    /**
     * @expectedException Cassandra\Exception\DivideByZeroException
     */
    public function testDivByZero()
    {
        $float1 = new Float("1");
        $float2 = new Float("0");
        $float1->div($float2);
    }

    public function testMod()
    {
        $float1 = new Float("1");
        $float2 = new Float("2");
        $this->assertEquals(1, (float)$float1->mod($float2), "", self::EPSILON);
    }

    public function testAbs()
    {
        $float1 = new Float("-123.123");
        $this->assertEquals(123.123, (float)$float1->abs(), "", self::EPSILON);
    }

    public function testNeg()
    {
        $float1 = new Float("123.123");
        $this->assertEquals(-123.123, (float)$float1->neg(), "", self::EPSILON);
    }

    public function testSqrt()
    {
        $float1 = new Float("4.0");
        $this->assertEquals(2.0, (float)$float1->sqrt(), "", self::EPSILON);
    }

    /**
     * @dataProvider equalTypes
     */
    public function testCompareEquals($value1, $value2)
    {
        $this->assertEquals($value1, $value2);
        $this->assertTrue($value1 == $value2);
    }

    public function equalTypes()
    {
        return array(
            array(new Float('3.14159'), new Float('3.14159')),
            array(new Float(1.1), new Float(1.1)),
        );
    }

    /**
     * @dataProvider notEqualTypes
     */
    public function testCompareNotEquals($value1, $value2)
    {
        $this->assertNotEquals($value1, $value2);
        $this->assertFalse($value1 == $value2);
    }

    public function notEqualTypes()
    {
        return array(
            array(new Float('3.14159'), new Float('3.1415')),
            array(new Float(1.1), new Float(2.2)),
        );
    }
}
