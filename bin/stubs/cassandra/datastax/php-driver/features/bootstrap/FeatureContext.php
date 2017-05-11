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

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

require_once __DIR__.'/../../support/ccm.php';

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context, SnippetAcceptingContext
{
    private $workingDir;
    private $phpBin;
    private $phpBinOptions;
    private $process;
    private $webServerProcess;
    private $webServerURL;
    private $lastResponse;
    private $ccm;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct($cassandra_version)
    {
        $this->ccm = new \CCM($cassandra_version);
    }

    /**
     * Cleans test folders in the temporary directory.
     *
     * @BeforeSuite
     * @AfterSuite
     */
    public static function cleanTestFolders()
    {
        if (is_dir($dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'php-driver')) {
            self::clearDirectory($dir);
        }
        $ccm = new \CCM('', '');
        $ccm->removeAllClusters();
    }

    /**
     * Prepares test folders in the temporary directory.
     *
     * @BeforeScenario
     */
    public function prepareTestFolders()
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'php-driver'.DIRECTORY_SEPARATOR.
            md5(microtime() * rand(0, 10000));
        $phpFinder = new PhpExecutableFinder();
        if (false === $php = $phpFinder->find()) {
            throw new \RuntimeException('Unable to find the PHP executable.');
        }
        $this->workingDir               = $dir;
        $this->phpBin                   = $php;
        $this->process                  = new Process(null);
        $this->webServerProcess         = null;
        $this->webServerURL             = '';
        $this->lastResponse             = '';
    }

    /**
     * Perform scenario teardown operations
     *
     * @AfterScenario
     */
    public function scenarioTeardown()
    {
        $this->phpBinOptions            = '';
        $this->webServerURL             = '';
        $this->lastResponse             = '';

        $this->terminateWebServer();
    }

    /**
     * @Given a running Cassandra cluster
     */
    public function aRunningCassandraCluster()
    {
        $this->ccm->setup(1, 0);
        $this->ccm->start();
    }

    /**
     * @Given a running Cassandra cluster with :numberOfNodes nodes
     */
    public function aRunningCassandraClusterWithMultipleNode($numberOfNodes)
    {
        $this->ccm->setup($numberOfNodes, 0);
        $this->ccm->start();
    }

    /**
     * @Given a running cassandra cluster with SSL encryption
     */
    public function aRunningCassandraClusterWithSslEncryption()
    {
        $this->ccm->setupSSL();
        $this->ccm->start();
    }

    /**
     * @Given a running cassandra cluster with client certificate verification
     */
    public function aRunningCassandraClusterWithClientCertificateVerification()
    {
        $this->ccm->setupClientVerification();
        $this->ccm->start();
    }

    /**
     * @Given tracing is enabled
     */
    public function tracingIsEnabled()
    {
        $this->ccm->enableTracing(true);
    }

    /**
     * @Given tracing is disabled
     */
    public function tracingIsDisabled()
    {
        $this->ccm->enableTracing(false);
    }

    /**
     * @Given /^the following schema:$/
     */
    public function theFollowingSchema(PyStringNode $string)
    {
        $this->ccm->setupSchema((string) $string);
    }

    /**
     * @Given /^additional schema:$/
     */
    public function additionalSchema(PyStringNode $string)
    {
        $this->ccm->setupSchema((string) $string, false);
    }

    /**
     * @Given /^the following example:$/
     */
    public function theFollowingExample(PyStringNode $string)
    {
        $this->createFile($this->workingDir.'/example.php', (string) $string);
    }

    /**
     * @Given a file named :name with:
     */
    public function aFileNamedWith($name, PyStringNode $string)
    {
        $this->createFile($this->workingDir.'/'.$name, (string) $string);
    }

    private function fetchPath($url)
    {
        if (in_array('curl', get_loaded_extensions())) {
            $request = curl_init();

            curl_setopt($request, CURLOPT_URL, $url);
            curl_setopt($request, CURLOPT_HEADER, 0);
            curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($request, CURLOPT_USERAGENT, 'PHP Driver Tests');

            $content = curl_exec($request);
            curl_close($request);
        } else {
            $content = file_get_contents($url);
        }

        return $content;
    }

    /**
     * @When I go to :path
     */
    public function iGoTo($path)
    {
        if (!$this->webServerProcess) {
            $this->startWebServer();
        }

        for ($retries = 1; $retries <= 10; $retries++) {
            $contents = $this->fetchPath($this->webServerURL.$path);

            if ($contents === false) {
                $wait = $retries * 0.4;
                printf("Unable to fetch %s, attempt %d, retrying in %d\n",
                       $path, $retries, $wait);
                sleep($wait);
                continue;
            }

            break;
        }

        if ($contents === false) {
            echo 'Web Server STDOUT: ' . $this->webServerProcess->getOutput() . "\n";
            echo 'Web Server STDERR: ' . $this->webServerProcess->getErrorOutput() . "\n";

            throw new Exception(sprintf("Unable to fetch %s", $path));
        }

        $this->lastResponse = $contents;
    }

    /**
     * @Then I should see:
     */
    public function iShouldSee(TableNode $table)
    {
        $doc = new DOMDocument();
        $doc->loadHTML($this->lastResponse);
        $xpath = new DOMXpath($doc);
        $nodes = $xpath->query("//h2/a[@name='module_cassandra']/../following-sibling::*[position()=1][name()='table']");
        $html  = $nodes->item(0);
        $table = $table->getRowsHash();

        foreach ($html->childNodes as $tr) {
            $name  = trim($tr->childNodes->item(0)->textContent);
            $value = trim($tr->childNodes->item(1)->textContent);

            if (isset($table[$name])) {
                if ($value !== $table[$name]) {
                    throw new Exception(sprintf(
                        "Failed asserting the value of %s: %s expected, %s found",
                        $name, $table[$name], $value
                    ));
                }
                unset($table[$name]);
            }
        }

        if (!empty($table)) {
            throw new Exception(sprintf(
                "Unable to find the following values %s", var_export($table, true)
            ));
        }
    }

    /**
     * @When I go to :path :count times
     */
    public function iGoToTimes($path, $count)
    {
        for ($i = 0; $i < $count; $i++) {
            $this->iGoTo($path);
        }
    }

    /**
     * @When /^it is executed$/
     */
    public function itIsExecuted()
    {
        $this->execute();
    }

    /**
     * @When it is executed with trusted cert in the env
     */
    public function itIsExecutedWithTrustedCertInTheEnv()
    {
        $this->execute(array(
            'SERVER_CERT' => realpath(__DIR__.'/../../support/ssl/cassandra.pem'),
        ));
    }

    /**
     * @When it is executed with trusted and client certs, private key and passphrase in the env
     */
    public function itIsExecutedWithTrustedAndClientCertsPrivateKeyAndPassphraseInTheEnv()
    {
        $this->execute(array(
            'SERVER_CERT' => realpath(__DIR__.'/../../support/ssl/cassandra.pem'),
            'CLIENT_CERT' => realpath(__DIR__.'/../../support/ssl/driver.pem'),
            'PRIVATE_KEY' => realpath(__DIR__.'/../../support/ssl/driver.key'),
            'PASSPHRASE' => 'php-driver',
        ));
    }

    private function execute(array $env = array())
    {
        $this->process->setWorkingDirectory($this->workingDir);
        $this->process->setCommandLine(sprintf(
            '%s %s %s', $this->phpBin, $this->phpBinOptions, 'example.php'
        ));
        if (!empty($env)) {
            $this->process->setEnv(array_replace((array) $this->process->getEnv(), $env));
        }
        $this->process->run();
    }

    private function startWebServer()
    {
        $this->webServerURL = 'http://127.0.0.1:10000';
        $command = sprintf('exec %s -S "%s"', $this->phpBin, '127.0.0.1:10000');
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $command = sprintf('%s -S "%s"', $this->phpBin, '127.0.0.1:10000');
        }
        if ($this->phpBinOptions) {
            $command = sprintf("%s %s", $command, $this->phpBinOptions);
        }
        $this->webServerProcess = new Process($command, $this->workingDir);
        $this->webServerProcess->setCommandLine($command);
        $this->webServerProcess->start();
        echo 'Web Server Started: ' . $this->webServerProcess->getPid() . "\n";
        sleep(5);
    }

    private function terminateWebServer() {
        if ($this->webServerProcess) {
            echo 'Stopping Web Server: ' . $this->webServerProcess->getPid() . "\n";
            $this->webServerProcess->stop();
            $this->webServerProcess = null;
            echo "Web Server Stopped\n";
        }
    }

    /**
     * @Then /^its output should contain:$/
     */
    public function itsOutputShouldContain(PyStringNode $string)
    {
        PHPUnit_Framework_Assert::assertContains((string) $string, $this->getOutput());
    }

    /**
     * @Then /^its output should contain these lines in any order:$/
     */
    public function itsOutputShouldContainTheseLinesInAnyOrder(PyStringNode $string)
    {
        $expected = explode("\n", $string);
        sort($expected, SORT_STRING);
        $actual = explode("\n", $this->getOutput());
        sort($actual, SORT_STRING);
        PHPUnit_Framework_Assert::assertContains(implode("\n", $expected), implode("\n", $actual));
    }

    /**
     * @Given the following logger settings:
     */
    public function theFollowingLoggerSettings(PyStringNode $string)
    {
        $lines = preg_split("/\\r\\n|\\r|\\n/", $string->getRaw());
        foreach($lines as $key=>$line) {
            $this->phpBinOptions .= '-d '.$line.' ';
        }
    }

    /**
     * @Then a log file :filename should exist
     */
    public function aLogFileShouldExist($filename)
    {
        $absoluteFilename = $this->workingDir.DIRECTORY_SEPARATOR.((string) $filename);
        PHPUnit_Framework_Assert::assertFileExists($absoluteFilename);
    }

    /**
     * @Then the log file :filename should contain :contents
     */
    public function theLogFileShouldContain($filename, $contents)
    {
      $absoluteFilename = $this->workingDir.DIRECTORY_SEPARATOR.((string) $filename);
      PHPUnit_Framework_Assert::assertFileExists($absoluteFilename);
      PHPUnit_Framework_Assert::assertContains($contents, file_get_contents($absoluteFilename));
    }

    private function getOutput()
    {
        $output = $this->process->getErrorOutput().$this->process->getOutput();
        // Normalize the line endings in the output
        if ("\n" !== PHP_EOL) {
            $output = str_replace(PHP_EOL, "\n", $output);
        }

        return trim(preg_replace('/ +$/m', '', $output));
    }

    private function createFile($filename, $content)
    {
        $path = dirname($filename);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $autoload = realpath(__DIR__.'/../../vendor/autoload.php');
        $content  = preg_replace('/\<\?php/', "<?php include '$autoload';", $content, 1);
        file_put_contents($filename, $content);
    }

    private static function clearDirectory($path)
    {
        $files = scandir($path);
        array_shift($files);
        array_shift($files);
        foreach ($files as $file) {
            $file = $path.DIRECTORY_SEPARATOR.$file;
            if (is_dir($file)) {
                self::clearDirectory($file);
            } else {
                unlink($file);
            }
        }
        rmdir($path);
    }
}
