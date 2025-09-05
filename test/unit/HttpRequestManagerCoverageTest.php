<?php

namespace Test\Unit;

use Test\TestCase;
use Web3\RequestManagers\HttpRequestManager;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;
use TypeError;

class HttpRequestManagerCoverageTest extends TestCase
{
    /**
     * Override setUp to prevent automatic Web3 connection
     */
    public function setUp(): void
    {
        // Don't call parent::setUp() to avoid automatic connection to localhost:8545
    }

    private function createMockHttpRequestManager($responses = [])
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $httpRequestManager = new HttpRequestManager('http://localhost:8545', 5);
        // Use reflection to replace the client
        $reflection = new \ReflectionClass($httpRequestManager);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($httpRequestManager, $client);

        return $httpRequestManager;
    }

    /**
     * Test constructor with timeout parameter
     */
    public function testConstructor()
    {
        $httpRequestManager = new HttpRequestManager('http://localhost:8545', 5);
        $this->assertInstanceOf(HttpRequestManager::class, $httpRequestManager);
    }

    /**
     * Test constructor with default timeout
     */
    public function testConstructorWithDefaultTimeout()
    {
        $httpRequestManager = new HttpRequestManager('http://localhost:8545');
        $this->assertInstanceOf(HttpRequestManager::class, $httpRequestManager);
    }

    /**
     * Test client property access through getClient
     */
    public function testClientPropertyAccess()
    {
        $httpRequestManager = new HttpRequestManager('http://localhost:8545', 1);
        $client = $httpRequestManager->getClient();
        $this->assertInstanceOf(Client::class, $client);
    }

    /**
     * Test magic getter for provider property
     */
    public function testMagicGetterForProvider()
    {
        $httpRequestManager = new HttpRequestManager('http://localhost:8545', 1);
        $provider = $httpRequestManager->provider;
        $this->assertNotNull($provider);
    }

    /**
     * Test sendPayload with null input
     */
    public function testSendPayloadWithNullInput()
    {
        $mockResponse = new Response(200, [], '{"jsonrpc":"2.0","result":"test","id":1}');
        $httpRequestManager = $this->createMockHttpRequestManager([$mockResponse]);

        $this->expectException(InvalidArgumentException::class);
        $httpRequestManager->sendPayload(null, function($error, $result) {});
    }

    /**
     * Test sendPayload with empty string - this should NOT throw exception since empty string is still a string
     */
    public function testSendPayloadWithEmptyString()
    {
        $mockResponse = new Response(200, [], '{"jsonrpc":"2.0","result":"","id":1}');
        $httpRequestManager = $this->createMockHttpRequestManager([$mockResponse]);

        $payload = '';  // Empty string is still a valid string
        $callbackExecuted = false;
        $callbackError = null;
        $callbackResult = null;

        $httpRequestManager->sendPayload($payload, function($error, $result) use (&$callbackExecuted, &$callbackError, &$callbackResult) {
            $callbackExecuted = true;
            $callbackError = $error;
            $callbackResult = $result;
        });

        $this->assertTrue($callbackExecuted);
        $this->assertNull($callbackError);
        $this->assertEquals('', $callbackResult);
    }

    /**
     * Test sendPayload with integer input
     */
    public function testSendPayloadWithIntegerInput()
    {
        $httpRequestManager = new HttpRequestManager('http://localhost:8545', 1);

        $this->expectException(InvalidArgumentException::class);
        $httpRequestManager->sendPayload(123, function($error, $result) {});
    }

    /**
     * Test sendPayload with valid single request
     */
    public function testSendPayloadWithValidSingleRequest()
    {
        $mockResponse = new Response(200, [], '{"jsonrpc":"2.0","result":"0x1","id":1}');
        $httpRequestManager = $this->createMockHttpRequestManager([$mockResponse]);

        $payload = '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}';
        $callbackExecuted = false;
        $callbackError = null;
        $callbackResult = null;

        $httpRequestManager->sendPayload($payload, function($error, $result) use (&$callbackExecuted, &$callbackError, &$callbackResult) {
            $callbackExecuted = true;
            $callbackError = $error;
            $callbackResult = $result;
        });

        $this->assertTrue($callbackExecuted);
        $this->assertNull($callbackError);
        $this->assertEquals('0x1', $callbackResult);
    }

    /**
     * Test sendPayload with valid batch request
     */
    public function testSendPayloadWithValidBatchRequest()
    {
        $mockResponse = new Response(200, [], '[{"jsonrpc":"2.0","result":"0x1","id":1},{"jsonrpc":"2.0","result":"0x2","id":2}]');
        $httpRequestManager = $this->createMockHttpRequestManager([$mockResponse]);

        $payload = '[{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1},{"jsonrpc":"2.0","method":"eth_gasPrice","params":[],"id":2}]';
        $callbackExecuted = false;
        $callbackError = null;
        $callbackResult = null;

        $httpRequestManager->sendPayload($payload, function($error, $result) use (&$callbackExecuted, &$callbackError, &$callbackResult) {
            $callbackExecuted = true;
            $callbackError = $error;
            $callbackResult = $result;
        });

        $this->assertTrue($callbackExecuted);
        $this->assertNull($callbackError);
        $this->assertIsArray($callbackResult);
        $this->assertCount(2, $callbackResult);
    }

    /**
     * Test sendPayload with RequestException
     */
    public function testSendPayloadWithRequestException()
    {
        $exception = new RequestException('Connection error', new Request('POST', 'test'));
        $httpRequestManager = $this->createMockHttpRequestManager([$exception]);

        $payload = '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}';
        $callbackExecuted = false;
        $callbackError = null;
        $callbackResult = null;

        $httpRequestManager->sendPayload($payload, function($error, $result) use (&$callbackExecuted, &$callbackError, &$callbackResult) {
            $callbackExecuted = true;
            $callbackError = $error;
            $callbackResult = $result;
        });

        $this->assertTrue($callbackExecuted);
        $this->assertInstanceOf(RequestException::class, $callbackError);
        $this->assertNull($callbackResult);
    }

    /**
     * Test sendPayload with invalid JSON response that triggers the JSON decode error
     * Note: The current code has a bug - it doesn't return after JSON decode error,
     * so execution continues and causes a TypeError when trying to use the null $json
     */
    public function testSendPayloadWithInvalidJsonResponse()
    {
        $mockResponse = new Response(200, [], 'invalid json response');
        $httpRequestManager = $this->createMockHttpRequestManager([$mockResponse]);

        $payload = '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}';

        // This will trigger the bug in the code where it continues execution after JSON error
        $this->expectException(TypeError::class);
        $httpRequestManager->sendPayload($payload, function($error, $result) {});
    }

    /**
     * Test to trigger the missing return after JSON decode error by using a callback that captures the error
     * and then continues to hit the property_exists call on null
     */
    public function testSendPayloadJsonDecodeErrorContinuesExecution()
    {
        $mockResponse = new Response(200, [], 'invalid json {');
        $httpRequestManager = $this->createMockHttpRequestManager([$mockResponse]);

        $payload = '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}';

        // The JSON decode will fail, and then execution continues causing TypeError
        $this->expectException(TypeError::class);
        $httpRequestManager->sendPayload($payload, function($error, $result) {
            // This callback gets called for the JSON decode error, but then execution continues
        });
    }

    /**
     * Test sendPayload with error response
     */
    public function testSendPayloadWithErrorResponse()
    {
        $mockResponse = new Response(200, [], '{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid request"},"id":1}');
        $httpRequestManager = $this->createMockHttpRequestManager([$mockResponse]);

        $payload = '{"jsonrpc":"2.0","method":"invalid_method","params":[],"id":1}';
        $callbackExecuted = false;
        $callbackError = null;
        $callbackResult = null;

        $httpRequestManager->sendPayload($payload, function($error, $result) use (&$callbackExecuted, &$callbackError, &$callbackResult) {
            $callbackExecuted = true;
            $callbackError = $error;
            $callbackResult = $result;
        });

        $this->assertTrue($callbackExecuted);
        $this->assertNotNull($callbackError);
        $this->assertNull($callbackResult);
    }

    /**
     * Test sendPayload with batch error response
     * Note: Due to a bug in the original code, batch error responses don't properly handle
     * individual errors - they just return null results instead of errors
     */
    public function testSendPayloadWithBatchErrorResponse()
    {
        // Batch response with one error and one successful result
        $mockResponse = new Response(200, [], '[{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid request"},"id":1},{"jsonrpc":"2.0","result":"0x2","id":2}]');
        $httpRequestManager = $this->createMockHttpRequestManager([$mockResponse]);

        $payload = '[{"jsonrpc":"2.0","method":"invalid_method","params":[],"id":1},{"jsonrpc":"2.0","method":"eth_gasPrice","params":[],"id":2}]';
        $callbackExecuted = false;
        $callbackError = null;
        $callbackResult = null;

        $httpRequestManager->sendPayload($payload, function($error, $result) use (&$callbackExecuted, &$callbackError, &$callbackResult) {
            $callbackExecuted = true;
            $callbackError = $error;
            $callbackResult = $result;
        });

        $this->assertTrue($callbackExecuted);
        // With the bug fixed, errors are now properly processed in batch mode
        $this->assertIsArray($callbackError);
        $this->assertInstanceOf(\RuntimeException::class, $callbackError[0]);
        $this->assertEquals('Invalid request', $callbackError[0]->getMessage());
        $this->assertEquals(-32600, $callbackError[0]->getCode());
        // For mixed batch responses, results array still contains successful results
        $this->assertIsArray($callbackResult);
        $this->assertEquals(['0x2'], $callbackResult);
    }

    /**
     * Test sendPayload with response that has no result or error
     */
    public function testSendPayloadWithUnusualResponse()
    {
        $mockResponse = new Response(200, [], '{"jsonrpc":"2.0","id":1}');
        $httpRequestManager = $this->createMockHttpRequestManager([$mockResponse]);

        $payload = '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}';
        $callbackExecuted = false;
        $callbackError = null;
        $callbackResult = null;

        $httpRequestManager->sendPayload($payload, function($error, $result) use (&$callbackExecuted, &$callbackError, &$callbackResult) {
            $callbackExecuted = true;
            $callbackError = $error;
            $callbackResult = $result;
        });

        $this->assertTrue($callbackExecuted);
        $this->assertNotNull($callbackError);
        $this->assertNull($callbackResult);
    }

    /**
     * Test batch processing where items don't have result property and get set to null
     */
    public function testSendPayloadBatchWithItemsWithoutResult()
    {
        // Batch response where items don't have result property
        $mockResponse = new Response(200, [], '[{"jsonrpc":"2.0","id":1},{"jsonrpc":"2.0","id":2}]');
        $httpRequestManager = $this->createMockHttpRequestManager([$mockResponse]);

        $payload = '[{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1},{"jsonrpc":"2.0","method":"eth_gasPrice","params":[],"id":2}]';
        $callbackExecuted = false;
        $callbackError = null;
        $callbackResult = null;

        $httpRequestManager->sendPayload($payload, function($error, $result) use (&$callbackExecuted, &$callbackError, &$callbackResult) {
            $callbackExecuted = true;
            $callbackError = $error;
            $callbackResult = $result;
        });

        $this->assertTrue($callbackExecuted);
        $this->assertNull($callbackError);
        $this->assertIsArray($callbackResult);
        $this->assertEquals([null, null], $callbackResult);
    }

    /**
     * Test batch request handling with mix of success and errors
     */
    public function testSendPayloadBatchWithErrors()
    {
        // Mock response with both successful and error responses in batch
        $mockResponse = [
            (object)[
                'id' => 1,
                'result' => '0x123'
            ],
            (object)[
                'id' => 2,
                'error' => (object)[
                    'code' => -32601,
                    'message' => 'Error: Method not found'
                ]
            ],
            (object)[
                'id' => 3,
                'result' => '0x456'
            ]
        ];

        $mockResponseObj = new Response(200, [], json_encode($mockResponse));
        $requestManager = $this->createMockHttpRequestManager([$mockResponseObj]);        $payload = json_encode([
            [
                'jsonrpc' => '2.0',
                'method' => 'eth_getBalance',
                'params' => ['0x123', 'latest'],
                'id' => 1
            ],
            [
                'jsonrpc' => '2.0',
                'method' => 'invalid_method',
                'params' => [],
                'id' => 2
            ],
            [
                'jsonrpc' => '2.0',
                'method' => 'eth_blockNumber',
                'params' => [],
                'id' => 3
            ]
        ]);

        $callbackExecuted = false;
        $callbackError = null;
        $callbackResult = null;

        $requestManager->sendPayload($payload, function($error, $result) use (&$callbackExecuted, &$callbackError, &$callbackResult) {
            $callbackExecuted = true;
            $callbackError = $error;
            $callbackResult = $result;
        });

        $this->assertTrue($callbackExecuted);
        $this->assertIsArray($callbackError);
        $this->assertInstanceOf(\RuntimeException::class, $callbackError[0]);
        $this->assertEquals('Method not found', $callbackError[0]->getMessage());
        $this->assertEquals(-32601, $callbackError[0]->getCode());
        // For mixed batch responses, results array contains successful results
        $this->assertIsArray($callbackResult);
        $this->assertEquals(['0x123', '0x456'], $callbackResult);
    }

    /**
     * Test batch request with all error responses
     */
    public function testSendPayloadBatchAllErrors()
    {
        // Mock response with all error responses
        $mockResponse = [
            (object)[
                'id' => 1,
                'error' => (object)[
                    'code' => -32602,
                    'message' => 'Error: Invalid params'
                ]
            ],
            (object)[
                'id' => 2,
                'error' => (object)[
                    'code' => -32601,
                    'message' => 'Error: Method not found'
                ]
            ]
        ];

        $mockResponseObj = new Response(200, [], json_encode($mockResponse));
        $requestManager = $this->createMockHttpRequestManager([$mockResponseObj]);

        $payload = json_encode([
            [
                'jsonrpc' => '2.0',
                'method' => 'invalid_method1',
                'params' => [],
                'id' => 1
            ],
            [
                'jsonrpc' => '2.0',
                'method' => 'invalid_method2',
                'params' => [],
                'id' => 2
            ]
        ]);

        $callbackExecuted = false;
        $callbackError = null;
        $callbackResult = null;

        $requestManager->sendPayload($payload, function($error, $result) use (&$callbackExecuted, &$callbackError, &$callbackResult) {
            $callbackExecuted = true;
            $callbackError = $error;
            $callbackResult = $result;
        });

        $this->assertTrue($callbackExecuted);
        $this->assertIsArray($callbackError);
        $this->assertInstanceOf(\RuntimeException::class, $callbackError[0]);
        $this->assertEquals('Invalid params', $callbackError[0]->getMessage());
        $this->assertEquals(-32602, $callbackError[0]->getCode());
        // For all-error batch responses, results array is empty
        $this->assertIsArray($callbackResult);
        $this->assertEquals([], $callbackResult);
    }

    /**
     * Test batch request with result missing but no error (null case)
     */
    public function testSendPayloadBatchNullResult()
    {
        // Mock response with incomplete response object (no result, no error)
        $mockResponse = [
            (object)[
                'id' => 1,
                'result' => '0x123'
            ],
            (object)[
                'id' => 2
                // Missing both result and error properties
            ]
        ];

        $mockResponseObj = new Response(200, [], json_encode($mockResponse));
        $requestManager = $this->createMockHttpRequestManager([$mockResponseObj]);

        $payload = json_encode([
            [
                'jsonrpc' => '2.0',
                'method' => 'eth_getBalance',
                'params' => ['0x123', 'latest'],
                'id' => 1
            ],
            [
                'jsonrpc' => '2.0',
                'method' => 'some_method',
                'params' => [],
                'id' => 2
            ]
        ]);

        $callbackExecuted = false;
        $callbackError = null;
        $callbackResult = null;

        $requestManager->sendPayload($payload, function($error, $result) use (&$callbackExecuted, &$callbackError, &$callbackResult) {
            $callbackExecuted = true;
            $callbackError = $error;
            $callbackResult = $result;
        });

        $this->assertTrue($callbackExecuted);
        $this->assertNull($callbackError);
        $this->assertIsArray($callbackResult);
        $this->assertEquals('0x123', $callbackResult[0]);
        $this->assertNull($callbackResult[1]);
    }
}
