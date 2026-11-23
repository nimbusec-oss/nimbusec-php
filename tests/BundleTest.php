<?php

use PHPUnit\Framework\TestCase;
use Nimbusec\API;

class BundleTest extends TestCase
{
    private $api;
    
    public function setUp(): void
    {
        $this->api = new API(
            getenv("SDK_KEY"),
            getenv("SDK_SECRET"),
            getenv("SDK_URL")
        );
    }

    // ========================================= [ BUNDLE ] =========================================

    public function testFindBundles()
    {
        $bundles = $this->api->findBundles();
        $this->assertIsArray($bundles);
        return $bundles[0];
    }

    /**
     * @depends testFindBundles
     */
    public function testFindValidBundle($bundle)
    {
        $this->assertArrayHasKey("contingent", $bundle);
    }
}
