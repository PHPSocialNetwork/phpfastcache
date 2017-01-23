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
class UuidTest extends \PHPUnit_Framework_TestCase
{
    public function testGeneratesUniqueUuids()
    {
        for ($i = 0; $i < 10000; $i++) {
            $this->assertNotEquals((string) new Uuid(), (string) new Uuid());
        }
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
            array(new Uuid('2a5072fa-7da4-4ccd-a9b4-f017a3872304'), new Uuid('2a5072fa-7da4-4ccd-a9b4-f017a3872304')),
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
            array(new Uuid('2a5072fa-7da4-4ccd-a9b4-f017a3872304'), new Uuid('3b5072fa-7da4-4ccd-a9b4-f017a3872304')),
        );
    }

    /**
     * Ensure UUIDs are unique for fork/child processes
     *
     * This test will ensure that the PHP driver is producing unique UUIDs for
     * all child processes that get created during fork() operations in web
     * servers (e.g. Apache, nginx, ...etc).
     *
     * @test
     * @ticket PHP-115
     */
    public function testUniqueInChild() {
        if (!function_exists("pcntl_fork")) {
            $this->markTestSkipped("Unable to Execute testUniqueInChild Unit Test: pcntl_fork() does not exists");
        } else {
            // Create a PHP script to call within a PHPUnit test (exit call fails test)
            $script = <<<EOF
<?php
// Get and open the file for appending child UUIDs
\$uuidsFilename = \$_SERVER['argv'][1];
\$numberOfForks = \$_SERVER['argv'][2];

// Create requested children process; create UUIDs and append to a file
\$children = array();
foreach (range(1, \$numberOfForks) as \$i) {
    // Create the child process
    \$pid = pcntl_fork();

    // Ensure the child process was create successfully
    if (\$pid < 0) {
        die("Unable to Create Fork: Unique UUID test cannot complete");
    } else if (\$pid === 0) {
        // Create a UUID and add it to the file
        \$uuid = new \Cassandra\Uuid();
        file_put_contents(\$uuidsFilename, \$uuid->uuid() . PHP_EOL, FILE_APPEND);

        // Terminate child process
        exit(0);
    } else {
        // Parent process: Add the process ID to force waiting on children
        \$children[] = \$pid;
    }
}

// Wait on each child process to finish
foreach (\$children as \$pid) {
    pcntl_waitpid(\$pid, \$status);
}
?>
EOF;
            $numProcesses = 64;

            // Execute the PHP script passing in the filename for the UUIDs to be stored
            $uuidsFilename = tempnam(sys_get_temp_dir(), "uuid");
            $scriptFilename = tempnam(sys_get_temp_dir(), "uuid");
            file_put_contents($scriptFilename, $script, FILE_APPEND);
            exec(PHP_BINARY . " {$scriptFilename} {$uuidsFilename} $numProcesses");
            unlink($scriptFilename);

            // Get the contents of the file
            $uuids = file($uuidsFilename);
            unlink($uuidsFilename);

            // Ensure all the UUIDs are unique
            $this->assertEquals($numProcesses, count(array_unique($uuids)));
        }
    }
}
