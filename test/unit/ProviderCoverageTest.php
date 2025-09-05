<?php

namespace Test\Unit;

use Test\TestCase;
use Web3\RequestManagers\RequestManager;
use Web3\Providers\Provider;

class ProviderCoverageTest extends TestCase
{
    /**
     * testConstructor
     *
     * @return void
     */
    public function testConstructor()
    {
        $requestManager = new RequestManager('http://localhost:8545');
        $provider = new Provider($requestManager);

        $this->assertInstanceOf(Provider::class, $provider);
        $this->assertEquals($requestManager, $provider->getRequestManager());
    }

    /**
     * testGetRequestManager
     *
     * @return void
     */
    public function testGetRequestManager()
    {
        $requestManager = new RequestManager('http://localhost:8545');
        $provider = new Provider($requestManager);

        $this->assertEquals($requestManager, $provider->getRequestManager());
    }

    /**
     * testGetIsBatch
     *
     * @return void
     */
    public function testGetIsBatch()
    {
        $requestManager = new RequestManager('http://localhost:8545');
        $provider = new Provider($requestManager);

        $this->assertFalse($provider->getIsBatch());
    }

    /**
     * testMagicGetWithExistingGetter
     *
     * @return void
     */
    public function testMagicGetWithExistingGetter()
    {
        $requestManager = new RequestManager('http://localhost:8545');
        $provider = new Provider($requestManager);

        // Test accessing requestManager via magic getter
        $this->assertEquals($requestManager, $provider->requestManager);

        // Test accessing isBatch via magic getter
        $this->assertFalse($provider->isBatch);
    }

    /**
     * testMagicGetWithNonExistentGetter
     *
     * @return void
     */
    public function testMagicGetWithNonExistentGetter()
    {
        $requestManager = new RequestManager('http://localhost:8545');
        $provider = new Provider($requestManager);

        // Test accessing non-existent property returns false
        $this->assertFalse($provider->nonExistentProperty);
    }

    /**
     * testMagicSetWithNonExistentSetter
     *
     * @return void
     */
    public function testMagicSetWithNonExistentSetter()
    {
        $requestManager = new RequestManager('http://localhost:8545');
        $provider = new Provider($requestManager);

        // Test setting non-existent property - the assignment succeeds but __get returns false
        $provider->nonExistentProperty = 'test';
        $this->assertFalse($provider->nonExistentProperty);

        // But the __set method itself would return false if we could call it directly
        $setResult = $provider->__set('nonExistentProperty', 'test2');
        $this->assertFalse($setResult);
    }
}
