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
class BigintTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid integer value: ''
     */
    public function testThrowsWhenCreatingFromEmpty()
    {
        new Bigint("");
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid integer value: 'invalid'
     */
    public function testThrowsWhenCreatingFromInvalid()
    {
        new Bigint("invalid");
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid characters were found in value: '123.123'
     */
    public function testThrowsWhenCreatingFromInvalidTrailingChars()
    {
        new Bigint("123.123");
    }

    /**
     * @dataProvider      outOfRangeStrings
     * @expectedException RangeException
     */
    public function testThrowsWhenCreatingOutOfRange($string)
    {
        new Bigint($string);
    }

    public function outOfRangeStrings()
    {
        return array(
            array("9223372036854775808"),
            array("-9223372036854775809"),
        );
    }

    /**
     * @dataProvider validStrings
     */
    public function testCorrectlyParsesStrings($number, $expected)
    {
        $number = new Bigint($number);
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
        $bigint = new Bigint($number);
        $this->assertEquals((int)$number, $bigint->toInt());
        $this->assertEquals((float)(int)$number, $bigint->toDouble());
        $this->assertEquals((string)(int)$number, (string)$bigint);
    }

    public function validNumbers()
    {
        return array(
            array(0.123),
            array(123),
        );
    }

    public function testIs32Bit()
    {
        if (PHP_INT_MAX == 9223372036854775807) {
            $this->markTestSkipped("Not a valid test on 64-bit machinces");
        }
    }

    /**
     * @depends testIs32Bit
     * @expectedException         RangeException
     * @expectedExceptionMessage  Value is too big
     */
    public function testOverflowTooBig()
    {
        $bigint = new Bigint("9223372036854775807");
        $i = $bigint->toInt();
    }

    /**
     * @depends testIs32Bit
     * @expectedException         RangeException
     * @expectedExceptionMessage  Value is too small
     */
    public function testOverflowTooSmall()
    {
        $bigint = new Bigint("-9223372036854775808");
        $i = $bigint->toInt();
    }

    public function testAdd()
    {
        $bigint1 = new Bigint("1");
        $bigint2 = new Bigint("2");
        $this->assertEquals(3, (int)$bigint1->add($bigint2));
    }

    public function testSub()
    {
        $bigint1 = new Bigint("1");
        $bigint2 = new Bigint("2");
        $this->assertEquals(-1, (int)$bigint1->sub($bigint2));
    }

    public function testMul()
    {
        $bigint1 = new Bigint("1");
        $bigint2 = new Bigint("2");
        $this->assertEquals(2, (int)$bigint1->mul($bigint2));
    }

    public function testDiv()
    {
        $bigint1 = new Bigint("1");
        $bigint2 = new Bigint("2");
        $this->assertEquals(0, (int)$bigint1->div($bigint2));
    }

    /**
     * @expectedException Cassandra\Exception\DivideByZeroException
     */
    public function testDivByZero()
    {
        $bigint1 = new Bigint("1");
        $bigint2 = new Bigint("0");
        $bigint1->div($bigint2);
    }

    public function testMod()
    {
        $bigint1 = new Bigint("1");
        $bigint2 = new Bigint("2");
        $this->assertEquals(1, (int)$bigint1->mod($bigint2));
    }

    /**
     * @expectedException Cassandra\Exception\DivideByZeroException
     */
    public function testModByZero()
    {
        $bigint1 = new Bigint("1");
        $bigint2 = new Bigint("0");
        $bigint1->mod($bigint2);
    }

    public function testAbs()
    {
        $bigint1 = new Bigint("-1");
        $this->assertEquals(1, (int)$bigint1->abs());
    }

    /**
     * @expectedException RangeException
     */
    public function testAbsMinimum()
    {
        Bigint::min()->abs();
    }

    public function testNeg()
    {
        $bigint1 = new Bigint("1");
        $this->assertEquals(-1, (int)$bigint1->neg());
    }

    public function testSqrt()
    {
        $bigint1 = new Bigint("4");
        $this->assertEquals(2, (int)$bigint1->sqrt());
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
            array(new Bigint('99'), new Bigint('99')),
            array(new Bigint(42), new Bigint(42))
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
            array(new Bigint('99'), new Bigint('999')),
            array(new Bigint(41), new Bigint(42))
        );
    }
}
