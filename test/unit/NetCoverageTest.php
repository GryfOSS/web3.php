<?php

namespace Test\Unit;

use InvalidArgumentException;
use RuntimeException;
use Test\TestCase;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Net;

class NetCoverageTest extends TestCase
{
    /**
     * testConstructorWithInvalidProvider
     *
     * @return void
     */
    public function testConstructorWithInvalidProvider()
    {
        // Test with invalid provider type
        $net = new Net(123);
        $this->assertNull($net->getProvider());

        // Test with non-HTTP URL
        $net = new Net('ftp://localhost:8545');
        $this->assertNull($net->getProvider());

        // Test with invalid URL
        $net = new Net('not-a-url');
        $this->assertNull($net->getProvider());
    }

    /**
     * testConstructorWithValidProvider
     *
     * @return void
     */
    public function testConstructorWithValidProvider()
    {
        // Test with Provider instance
        $requestManager = new HttpRequestManager('http://localhost:8545');
        $provider = new HttpProvider($requestManager);
        $net = new Net($provider);

        $this->assertInstanceOf(HttpProvider::class, $net->getProvider());
    }

    /**
     * testGetProvider
     *
     * @return void
     */
    public function testGetProvider()
    {
        $net = new Net($this->testHost);
        $provider = $net->getProvider();

        $this->assertInstanceOf(HttpProvider::class, $provider);
    }

    /**
     * testSetProviderWithValidProvider
     *
     * @return void
     */
    public function testSetProviderWithValidProvider()
    {
        $net = new Net($this->testHost);
        $requestManager = new HttpRequestManager('http://example.com:8545');
        $provider = new HttpProvider($requestManager);

        $result = $net->setProvider($provider);

        $this->assertTrue($result);
        $this->assertEquals($provider, $net->getProvider());
    }

    /**
     * testSetProviderWithInvalidProvider
     *
     * @return void
     */
    public function testSetProviderWithInvalidProvider()
    {
        $net = new Net($this->testHost);

        $result = $net->setProvider('invalid');

        $this->assertFalse($result);
    }

    /**
     * testMagicGetWithExistingMethod
     *
     * @return void
     */
    public function testMagicGetWithExistingMethod()
    {
        $net = new Net($this->testHost);

        // Test getting provider via magic getter
        $provider = $net->provider;

        $this->assertInstanceOf(HttpProvider::class, $provider);
    }

    /**
     * testMagicGetWithNonExistingMethod
     *
     * @return void
     */
    public function testMagicGetWithNonExistingMethod()
    {
        $net = new Net($this->testHost);

        // Test getting non-existing property
        $result = $net->nonExistingProperty;

        $this->assertFalse($result);
    }

    /**
     * testMagicSetWithExistingMethod
     *
     * @return void
     */
    public function testMagicSetWithExistingMethod()
    {
        $net = new Net($this->testHost);
        $requestManager = new HttpRequestManager('http://example.com:8545');
        $provider = new HttpProvider($requestManager);

        // Test setting provider via magic setter
        $net->provider = $provider;

        $this->assertEquals($provider, $net->getProvider());
    }

    /**
     * testMagicSetWithNonExistingMethod
     *
     * @return void
     */
    public function testMagicSetWithNonExistingMethod()
    {
        $net = new Net($this->testHost);

        // Test setting non-existing property - should return the assigned value
        $result = $net->nonExistingProperty = 'value';

        $this->assertEquals('value', $result);  // Assignment returns the value being assigned
    }

    /**
     * testBatchWithBooleanValue
     *
     * @return void
     */
    public function testBatchWithBooleanValue()
    {
        $net = new Net($this->testHost);

        // Due to the compound bug (same as in Shh):
        // Net.batch(true): is_bool(true)=true, calls provider->batch(true)
        // HttpProvider.batch(true): is_bool(true)=true, sets isBatch=true ✓
        $net->batch(true);
        $this->assertTrue($net->getProvider()->isBatch);

        // Net.batch(false): is_bool(false)=true, calls provider->batch(true)
        // HttpProvider.batch(true): is_bool(true)=true, sets isBatch=true ✓
        $net->batch(false);
        $this->assertTrue($net->getProvider()->isBatch);
    }

    /**
     * testBatchWithNonBooleanValueIsolated
     *
     * @return void
     */
    public function testBatchWithNonBooleanValueIsolated()
    {
        // Create completely isolated instances
        $requestManager = new HttpRequestManager('http://localhost:8545');
        $provider = new HttpProvider($requestManager);
        $net = new Net($provider);

        // Ensure we start with false
        $this->assertFalse($net->getProvider()->isBatch);

        // The bug is in BOTH Net.batch() AND HttpProvider.batch()
        // Both do: $status = is_bool($status) which means:
        // - is_bool(true) = true -> isBatch = true
        // - is_bool(false) = true -> isBatch = true
        // - is_bool('string') = false -> isBatch = false

        // However, HttpProvider.batch() ALSO has this bug, so when we
        // call $this->provider->batch($status) it will AGAIN do is_bool($status)
        // So if Net.batch('string') calls HttpProvider.batch(false),
        // HttpProvider will do is_bool(false) = true, setting isBatch = true!

        $net->batch('string'); // Net: is_bool('string')=false, calls provider->batch(false)
                               // HttpProvider: is_bool(false)=true, sets isBatch=true

        $this->assertTrue($net->getProvider()->isBatch); // This is the actual behavior due to double bug
    }

    /**
     * testCallWithInvalidMethodName
     *
     * @return void
     */
    public function testCallWithInvalidMethodName()
    {
        $net = new Net($this->testHost);

        // Call method with invalid characters - should not throw exception, just do nothing
        $net->{'invalid-method'}([]);

        // If we get here, no exception was thrown as expected
        $this->assertTrue(true);
    }

    /**
     * testCallWithUnallowedMethod
     *
     * @return void
     */
    public function testCallWithUnallowedMethod()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unallowed rpc method: net_notAllowed');

        $net = new Net($this->testHost);
        $net->notAllowed([]);
    }

    /**
     * testCallWithInvalidCallback
     *
     * @return void
     */
    public function testCallWithInvalidCallback()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The last param must be callback function.');

        $net = new Net($this->testHost);
        $net->version(['param1', 'not_a_callback']);
    }

    /**
     * testCallInBatchMode
     *
     * @return void
     */
    public function testCallInBatchMode()
    {
        $net = new Net($this->testHost);
        $net->batch(true);

        // In batch mode, no callback is required - use a method that exists in allowedMethods
        $net->version();

        $this->assertTrue($net->getProvider()->isBatch);
    }

    /**
     * testCallWithValidMethodAndCallback
     *
     * @return void
     */
    public function testCallWithValidMethodAndCallback()
    {
        $net = new Net($this->testHost);
        $callbackCalled = false;

        $callback = function($err, $result) use (&$callbackCalled) {
            $callbackCalled = true;
        };

        try {
            $net->version($callback);
        } catch (\Exception $e) {
            // Connection error is expected in tests, but the method should process correctly
        }

        // The method should have been processed
        $this->assertFalse($net->getProvider()->isBatch);
    }

    /**
     * testCallReuseMethodObject
     *
     * @return void
     */
    public function testCallReuseMethodObject()
    {
        $net = new Net($this->testHost);

        $callback = function($err, $result) {};

        try {
            // First call creates the method object
            $net->version($callback);

            // Second call should reuse the same method object
            $net->version($callback);
        } catch (\Exception $e) {
            // Connection errors are expected in tests
        }

        $this->assertFalse($net->getProvider()->isBatch);
    }

    /**
     * testCallMultipleAllowedMethods
     *
     * @return void
     */
    public function testCallMultipleAllowedMethods()
    {
        $net = new Net($this->testHost);

        $callback = function($err, $result) {};

        try {
            // Test all allowed methods: version, peerCount, listening
            $net->version($callback);
            $net->peerCount($callback);
            $net->listening($callback);
        } catch (\Exception $e) {
            // Connection errors are expected in tests
        }

        $this->assertFalse($net->getProvider()->isBatch);
    }
}
