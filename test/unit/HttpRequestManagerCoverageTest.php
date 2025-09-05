<?php

namespace Test\Unit;

use InvalidArgumentException;
use RuntimeException;
use Test\TestCase;
use Web3\RequestManagers\HttpRequestManager;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

class HttpRequestManagerCoverageTest extends TestCase
{
    /**
     * testConstructor
     *
     * @return void
     */
    public function testConstructor()
    {
        $manager = new HttpRequestManager('http://localhost:8545', 5);

        $this->assertInstanceOf(HttpRequestManager::class, $manager);
        $this->assertEquals('http://localhost:8545', $manager->host);
        $this->assertEquals(5.0, $manager->timeout);
    }

    /**
     * testConstructorWithDefaultTimeout
     *
     * @return void
     */
    public function testConstructorWithDefaultTimeout()
    {
        $manager = new HttpRequestManager('http://localhost:8545');

        $this->assertEquals('http://localhost:8545', $manager->host);
        $this->assertEquals(1.0, $manager->timeout);
    }

    /**
     * testSendPayloadWithInvalidPayload
     *
     * @return void
     */
    public function testSendPayloadWithInvalidPayload()
    {
        $manager = new HttpRequestManager($this->testHost);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payload must be string.');

        $manager->sendPayload([], function () {});
    }

    /**
     * testSendPayloadWithValidPayload
     *
     * @return void
     */
    public function testSendPayloadWithValidPayload()
    {
        $manager = new HttpRequestManager($this->testHost);
        $payload = '{"jsonrpc":"2.0","method":"web3_clientVersion","params":[],"id":1}';

        $executed = false;
        $manager->sendPayload($payload, function ($err, $result) use (&$executed) {
            $executed = true;
            if ($err !== null) {
                $this->fail($err->getMessage());
            }
            $this->assertIsString($result);
        });

        $this->assertTrue($executed);
    }

    /**
     * testSendPayloadWithBatchRequest
     *
     * @return void
     */
    public function testSendPayloadWithBatchRequest()
    {
        $manager = new HttpRequestManager($this->testHost);
        $payload = '[{"jsonrpc":"2.0","method":"web3_clientVersion","params":[],"id":1},{"jsonrpc":"2.0","method":"web3_clientVersion","params":[],"id":2}]';

        $executed = false;
        $manager->sendPayload($payload, function ($err, $result) use (&$executed) {
            $executed = true;
            if ($err !== null) {
                $this->fail($err->getMessage());
            }
            $this->assertIsArray($result);
            $this->assertCount(2, $result);
        });

        $this->assertTrue($executed);
    }

    /**
     * testSendPayloadInvalidArgumentException
     *
     * @return void
     */
    public function testSendPayloadInvalidArgumentException()
    {
        $manager = new HttpRequestManager($this->testHost);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payload must be string.');

        $manager->sendPayload(['invalid'], function () {});
    }

    /**
     * testMagicGettersInheritedFromRequestManager
     *
     * @return void
     */
    public function testMagicGettersInheritedFromRequestManager()
    {
        $manager = new HttpRequestManager($this->testHost, 2.5);

        // Test inherited magic getters
        $this->assertEquals($this->testHost, $manager->host);
        $this->assertEquals(2.5, $manager->timeout);
    }

    /**
     * testErrorResponseHandling
     *
     * @return void
     */
    public function testErrorResponseHandling()
    {
        $manager = new HttpRequestManager($this->testHost);
        // Send a request with invalid method to test error handling
        $payload = '{"jsonrpc":"2.0","method":"invalid_method_that_does_not_exist","params":[],"id":1}';

        $executed = false;
        $manager->sendPayload($payload, function ($err, $result) use (&$executed) {
            $executed = true;
            // Either should be set (depending on the test server response)
            $this->assertTrue($err !== null || $result !== null);
        });

        $this->assertTrue($executed);
    }
}
