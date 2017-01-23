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

class MapIntegrationTest extends CollectionsIntegrationTest
{
    /**
     * Map with scalar types
     *
     * This test ensures that maps work with all Cassandra's
     * scalar types.
     *
     * @test
     * @dataProvider mapWithScalarTypes
     */
    public function testScalarTypes($type, $value) {
        $this->createTableInsertAndVerifyValueByIndex($type, $value);
        $this->createTableInsertAndVerifyValueByName($type, $value);
    }

    /**
     * Data provider for maps with scalar types
     */
    public function mapWithScalarTypes() {
        $mapKeyTypes = array_map(function ($cassandraType) {
            $mapType = Type::map($cassandraType[0], Type::int());
            $values = $cassandraType[1];
            $map = $mapType->create();
            for ($i = 0; $i < count($cassandraType[1]); $i++) {
                $map->set($values[$i], $i);
            }
            return array($mapType, $map);
        }, $this->scalarCassandraTypes());

        $mapValueTypes = array_map(function ($cassandraType) {
            $mapType = Type::map(Type::int(), $cassandraType[0]);
            $values = $cassandraType[1];
            $map = $mapType->create();
            for ($i = 0; $i < count($cassandraType[1]); $i++) {
                $map->set($i, $values[$i]);
            }
            return array($mapType, $map);
        }, $this->scalarCassandraTypes());

        return array_merge($mapKeyTypes, $mapValueTypes);
    }

    /**
     * Map with composite types
     *
     * This test ensures that maps work with other collections
     * and other composite types such as UDTs and tuples.
     *
     * @test
     * @ticket PHP-62
     * @ticket PHP-57
     * @ticket PHP-58
     * @dataProvider mapWithCompositeTypes
     */
    public function testCompositeTypes($type, $value) {
        $this->createTableInsertAndVerifyValueByIndex($type, $value);
        $this->createTableInsertAndVerifyValueByName($type, $value);
    }

    /**
     * Data provider for maps with composite types
     */
    public function mapWithCompositeTypes() {
        $mapKeyTypes = array_map(function ($cassandraType) {
            $mapType = Type::map($cassandraType[0], Type::int());
            $values = $cassandraType[1];
            $map = $mapType->create();
            for ($i = 0; $i < count($cassandraType[1]); $i++) {
                $map->set($values[$i], $i);
            }
            return array($mapType, $map);
        }, $this->compositeCassandraTypes());

        $mapValueTypes = array_map(function ($cassandraType) {
            $mapType = Type::map(Type::int(), $cassandraType[0]);
            $values = $cassandraType[1];
            $map = $mapType->create();
            for ($i = 0; $i < count($cassandraType[1]); $i++) {
                $map->set($i, $values[$i]);
            }
            return array($mapType, $map);
        }, $this->compositeCassandraTypes());

        return array_merge($mapKeyTypes, $mapValueTypes);
    }

    /**
     * Map with nested composite types
     *
     * This test ensures that maps work with other nested collections
     * and other nested composite types such as UDTs and tuples.
     *
     * @test
     * @ticket PHP-62
     * @ticket PHP-57
     * @ticket PHP-58
     * @dataProvider mapWithNestedTypes
     */
    public function testNestedTypes($type, $value) {
        $this->createTableInsertAndVerifyValueByIndex($type, $value);
        $this->createTableInsertAndVerifyValueByName($type, $value);
    }

    /**
     * Data provider for maps with nested composite types
     */
    public function mapWithNestedTypes() {
        $mapKeyTypes = array_map(function ($cassandraType) {
            $mapType = Type::map($cassandraType[0], Type::int());
            $values = $cassandraType[1];
            $map = $mapType->create();
            for ($i = 0; $i < count($cassandraType[1]); $i++) {
                $map->set($values[$i], $i);
            }
            return array($mapType, $map);
        }, $this->nestedCassandraTypes());

        $mapValueTypes = array_map(function ($cassandraType) {
            $mapType = Type::map(Type::int(), $cassandraType[0]);
            $values = $cassandraType[1];
            $map = $mapType->create();
            for ($i = 0; $i < count($cassandraType[1]); $i++) {
                $map->set($i, $values[$i]);
            }
            return array($mapType, $map);
        }, $this->nestedCassandraTypes());

        return array_merge($mapKeyTypes, $mapValueTypes);
    }

    /**
     * Bind statement with an empty map
     */
    public function testNull() {
        $mapType = Type::map(Type::int(), Type::int());
        $this->createTableInsertAndVerifyValueByIndex($mapType, $mapType->create());
        $this->createTableInsertAndVerifyValueByName($mapType, $mapType->create());
    }

    /**
     * Bind statement with an null map
     */
    public function testEmpty() {
        $mapType = Type::map(Type::int(), Type::int());
        $this->createTableInsertAndVerifyValueByIndex($mapType, null);
        $this->createTableInsertAndVerifyValueByName($mapType, null);
    }
}
