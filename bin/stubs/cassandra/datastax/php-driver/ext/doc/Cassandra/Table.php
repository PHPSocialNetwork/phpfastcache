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
 * A PHP representation of a table
 */
interface Table
{
    /**
     * Returns the name of this table
     * @return string Name of the table
     */
    function name();

    /**
     * Return a table's option by name
     * @return Cassandra\Value Value of an option by name
     */
    function option($name);

    /**
     * Returns all the table's options
     * @return array A dictionary of `string` and `Cassandra\Value pairs of the
     *               view's options.
     */
    function options();

    /**
     * Description of the table, if any
     * @return string Table description or null
     */
    function comment();

    /**
     * Returns read repair chance
     * @return float Read repair chance
     */
    function readRepairChance();

    /**
     * Returns local read repair chance
     * @return float Local read repair chance
     */
    function localReadRepairChance();

    /**
     * Returns GC grace seconds
     * @return int GC grace seconds
     */
    function gcGraceSeconds();

    /**
     * Returns caching options
     * @return string Caching options
     */
    function caching();

    /**
     * Returns bloom filter FP chance
     * @return float Bloom filter FP chance
     */
    function bloomFilterFPChance();

    /**
     * Returns memtable flush period in milliseconds
     * @return int Memtable flush period in milliseconds
     */
    function memtableFlushPeriodMs();

    /**
     * Returns default TTL.
     * @return int Default TTL.
     */
    function defaultTTL();

    /**
     * Returns speculative retry.
     * @return string Speculative retry.
     */
    function speculativeRetry();

    /**
     * Returns index interval
     * @return int Index interval
     */
    function indexInterval();

    /**
     * Returns compaction strategy class name
     * @return string Compaction strategy class name
     */
    function compactionStrategyClassName();

    /**
     * Returns compaction strategy options
     * @return Cassandra\Map Compaction strategy options
     */
    function compactionStrategyOptions();

    /**
     * Returns compression parameters
     * @return Cassandra\Map Compression parameters
     */
    function compressionParameters();

    /**
     * Returns whether or not the `populate_io_cache_on_flush` is true
     * @return boolean Value of `populate_io_cache_on_flush` or null
     */
    function populateIOCacheOnFlush();

    /**
     * Returns whether or not the `replicate_on_write` is true
     * @return boolean Value of `replicate_on_write` or null
     */
    function replicateOnWrite();

    /**
     * Returns the value of `max_index_interval`
     * @return int Value of `max_index_interval` or null
     */
    function maxIndexInterval();

    /**
     * Returns the value of `min_index_interval`
     * @return int Value of `min_index_interval` or null
     */
    function minIndexInterval();

    /**
     * Returns column by name
     * @param  string           $name Name of the column
     * @return Cassandra\Column Column instance
     */
    function column($name);

    /**
     * Returns all columns in this table
     * @return array A list of `Cassandra\Column` instances
     */
    function columns();

    /**
     * Returns the partition key columns of the table
     * @return array A list of of `Cassandra\Column` instances
     */
     function partitionKey();

    /**
     * Returns both the partition and clustering key columns of the table
     * @return array A list of of `Cassandra\Column` instances
     */
     function primaryKey();

    /**
     * Returns the clustering key columns of the table
     * @return array A list of of `Cassandra\Column` instances
     */
     function clusteringKey();

    /**
     *
     * @return array A list of cluster column orders ('asc' and 'desc')
     */
     function clusteringOrder();
}
