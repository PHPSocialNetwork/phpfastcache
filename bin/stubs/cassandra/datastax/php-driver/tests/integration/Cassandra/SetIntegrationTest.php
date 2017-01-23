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

class SetIntegrationTest extends CollectionsIntegrationTest
{
    /**
     * Set with scalar types
     *
     * This test ensures that sets work with all Cassandra's
     * scalar types.
     *
     * @test
     * @dataProvider setWithScalarTypes
     */
    public function testScalarTypes($type, $value) {
        $this->createTableInsertAndVerifyValueByIndex($type, $value);
        $this->createTableInsertAndVerifyValueByName($type, $value);
    }

    /**
     * Data provider for sets with scalar types
     */
    public function setWithScalarTypes() {
        return array_map(function ($cassandraType) {
            $setType = Type::set($cassandraType[0]);
            $set = $setType->create();
            foreach ($cassandraType[1] as $value) {
                $set->add($value);
            }
            return array($setType, $set);
        }, $this->scalarCassandraTypes());
    }

    /**
     * Set with composite types
     *
     * This test ensures that sets work with other nested collections
     * and other composite types such as UDTs and tuples.
     *
     * @test
     * @ticket PHP-62
     * @ticket PHP-57
     * @ticket PHP-58
     * @dataProvider setWithCompositeTypes
     */
    public function testCompositeTypes($type, $value) {
        $this->createTableInsertAndVerifyValueByIndex($type, $value);
        $this->createTableInsertAndVerifyValueByName($type, $value);
    }

    /**
     * Data provider for sets with composite types
     */
    public function setWithCompositeTypes() {
        return array_map(function ($cassandraType) {
            $setType = Type::set($cassandraType[0]);
            $set = $setType->create();
            foreach ($cassandraType[1] as $value) {
                $set->add($value);
            }
            return array($setType, $set);
        }, $this->compositeCassandraTypes());
    }

    /**
     * Set with nested composite types
     *
     * This test ensures that sets work with other nested collections
     * and other composite types such as UDTs and tuples.
     *
     * @test
     * @ticket PHP-62
     * @ticket PHP-57
     * @ticket PHP-58
     * @dataProvider setWithNestedTypes
     */
    public function testNestedTypes($type, $value) {
        $this->createTableInsertAndVerifyValueByIndex($type, $value);
        $this->createTableInsertAndVerifyValueByName($type, $value);
    }

    /**
     * Data provider for sets with nested composite types
     */
    public function setWithNestedTypes() {
        return array_map(function ($cassandraType) {
            $setType = Type::set($cassandraType[0]);
            $set = $setType->create();
            foreach ($cassandraType[1] as $value) {
                $set->add($value);
            }
            return array($setType, $set);
        }, $this->nestedCassandraTypes());
    }

    /**
     * Bind statement with an empty set
     */
    public function testEmpty() {
        $setType = Type::set(Type::int());
        $this->createTableInsertAndVerifyValueByIndex($setType, $setType->create());
        $this->createTableInsertAndVerifyValueByName($setType, $setType->create());
    }

    /**
     * Bind statement with an null set
     */
    public function testNull() {
        $setType = Type::set(Type::int());
        $this->createTableInsertAndVerifyValueByIndex($setType, null);
        $this->createTableInsertAndVerifyValueByName($setType, null);
    }
}
