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

class CollectionIntegrationTest extends CollectionsIntegrationTest
{
    /**
     * List with scalar types
     *
     * This test ensures that lists work with all Cassandra's
     * scalar types.
     *
     * @test
     * @dataProvider collectionWithScalarTypes
     */
    public function testScalarTypes($type, $value) {
        $this->createTableInsertAndVerifyValueByIndex($type, $value);
        $this->createTableInsertAndVerifyValueByName($type, $value);
    }

    /**
     * Data provider for lists with scalar types
     */
    public function collectionWithScalarTypes() {
        return array_map(function ($cassandraType) {
            $listType = Type::collection($cassandraType[0]);
            $list = $listType->create();
            foreach ($cassandraType[1] as $value) {
                $list->add($value);
            }
            return array($listType, $list);
        }, $this->scalarCassandraTypes());
    }

    /**
     * List with composite types
     *
     * This test ensures that lists work with other nested collections
     * and other composite types such as UDTs and tuples.
     *
     * @test
     * @ticket PHP-62
     * @ticket PHP-57
     * @ticket PHP-58
     * @dataProvider collectionWithCompositeTypes
     */
    public function testCompositeTypes($type, $value) {
        $this->createTableInsertAndVerifyValueByIndex($type, $value);
        $this->createTableInsertAndVerifyValueByName($type, $value);
    }

    /**
     * Data provider for lists with composite types
     */
    public function collectionWithCompositeTypes() {
        return array_map(function ($cassandraType) {
            $listType = Type::collection($cassandraType[0]);
            $list = $listType->create();
            foreach ($cassandraType[1] as $value) {
                $list->add($value);
            }
            return array($listType, $list);
        }, $this->compositeCassandraTypes());
    }

    /**
     * List with nested composite types
     *
     * This test ensures that lists work with other nested collections
     * and other composite types such as UDTs and tuples.
     *
     * @test
     * @ticket PHP-62
     * @ticket PHP-57
     * @ticket PHP-58
     * @dataProvider collectionWithNestedTypes
     */
    public function testNestedTypes($type, $value) {
        $this->createTableInsertAndVerifyValueByIndex($type, $value);
        $this->createTableInsertAndVerifyValueByName($type, $value);
    }

    /**
     * Data provider for lists with nested composite types
     */
    public function collectionWithNestedTypes() {
        return array_map(function ($cassandraType) {
            $listType = Type::collection($cassandraType[0]);
            $list = $listType->create();
            foreach ($cassandraType[1] as $value) {
                $list->add($value);
            }
            return array($listType, $list);
        }, $this->nestedCassandraTypes());
    }

    /**
     * Bind statement with an empty list
     *
     * @test
     */
    public function testEmpty() {
        $listType = Type::Collection(Type::int());
        $this->createTableInsertAndVerifyValueByIndex($listType, $listType->create());
        $this->createTableInsertAndVerifyValueByName($listType, $listType->create());
    }

    /**
     * Bind statement a null list
     *
     * @test
     */
    public function testNull() {
        $listType = Type::Collection(Type::int());
        $this->createTableInsertAndVerifyValueByIndex($listType, null);
        $this->createTableInsertAndVerifyValueByName($listType, null);
    }
}
