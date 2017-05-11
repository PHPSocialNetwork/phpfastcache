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
class VarintTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException         InvalidArgumentException
     * @expectedExceptionMessage  Invalid integer value: '123.123', base: 10
     */
    public function testThrowsWhenCreatingNotAnInteger()
    {
        new Varint("123.123");
    }

    /**
     * @dataProvider validStrings
     */
    public function testCorrectlyParsesStrings($number, $expected)
    {
        $number = new Varint($number);
        $this->assertEquals($expected, $number->value());
        $this->assertEquals($expected, (string) $number);
    }

    public function validStrings()
    {
        return array(
            array("123", "123"),
            array("0123", "83"),
            array("0x123", "291"),
            array("0b1010101", "85"),
            array("-123", "-123"),
            array("-0123", "-83"),
            array("-0x123", "-291") ,
            array("-0b1010101", "-85")
        );
    }

    /**
     * @dataProvider validNumbers
     */
    public function testFromNumbers($number)
    {
        $varint = new Varint($number);
        $this->assertEquals((int)$number, $varint->toInt());
        $this->assertEquals((float)(int)$number, $varint->toDouble());
        $this->assertEquals((string)(int)$number, (string)$varint);
    }

    public function validNumbers()
    {
        return array(
            array(0.123),
            array(123),
            array(123.123),
        );
    }

    /**
     * @expectedException         RangeException
     * @expectedExceptionMessage  Value is too big
     */
    public function testOverflowTooBig()
    {
        // This is too big on both 32-bit and 64-bit
        $varint = new Varint("9223372036854775808");
        $i = $varint->toInt();
    }

    /**
     * @expectedException         RangeException
     * @expectedExceptionMessage  Value is too small
     */
    public function testOverflowTooSmall()
    {
        // This is too small on both 32-bit and 64-bit
        $varint = new Varint("-9223372036854775809");
        $i = $varint->toInt();
    }

    public function testAdd()
    {
        $varint1 = new Varint("9223372036854775807");
        $varint2 = new Varint("1");
        $this->assertEquals("9223372036854775808", (string)$varint1->add($varint2));
    }

    public function testSub()
    {
        $varint1 = new Varint("-9223372036854775808");
        $varint2 = new Varint("1");
        $this->assertEquals("-9223372036854775809", (string)$varint1->sub($varint2));
    }

    public function testMul()
    {
        $varint1 = new Varint("9223372036854775807");
        $varint2 = new Varint("2");
        $this->assertEquals("18446744073709551614", (string)$varint1->mul($varint2));
    }

    public function testDiv()
    {
        $varint1 = new Varint("18446744073709551614");
        $varint2 = new Varint("9223372036854775807");
        $this->assertEquals(2, (int)$varint1->div($varint2));
    }

    /**
     * @expectedException Cassandra\Exception\DivideByZeroException
     */
    public function testDivByZero()
    {
        $varint1 = new Varint("1");
        $varint2 = new Varint("0");
        $varint1->div($varint2);
    }

    public function testMod()
    {
        $varint1 = new Varint("1");
        $varint2 = new Varint("18446744073709551614");
        $this->assertEquals(1, (int)$varint1->mod($varint2));
    }

    /**
     * @expectedException Cassandra\Exception\DivideByZeroException
     */
    public function testModByZero()
    {
        $varint1 = new Varint("1");
        $varint2 = new Varint("0");
        $varint1->mod($varint2);
    }

    public function testAbs()
    {
        $varint1 = new Varint("-18446744073709551614");
        $this->assertEquals("18446744073709551614", (string)$varint1->abs());
    }

    public function testNeg()
    {
        $varint1 = new Varint("18446744073709551614");
        $this->assertEquals("-18446744073709551614", (string)$varint1->neg());
    }

    public function testSqrt()
    {
        $varint1 = new Varint("340282366920938463389587631136930004996");
        $this->assertEquals("18446744073709551614", (string)$varint1->sqrt());
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
            array(new Varint('123456789123456789123456789'), new Varint('123456789123456789123456789')),
            array(new Varint(99), new Varint(99))
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
            array(new Varint('123456789123456789123456789'), new Varint('999999999999999999999999999')),
            array(new Varint(99), new Varint(999))
        );
    }
}
