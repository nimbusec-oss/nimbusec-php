<?php

use PHPUnit\Framework\TestCase;
use Nimbusec\API;

class DomainTest extends TestCase
{
    private $api;
    private $domain;

    public function setUp()
    {
        $this->api = new API(
            getenv("SDK_KEY"),
            getenv("SDK_SECRET"),
            getenv("SDK_URL")
        );

        $this->domain = array(
            "scheme" => "http",
            "name" => "www.testUrl.com",
            "deepScan" => "http://www.testUrl.com",
            "fastScans" => array(
                    "http://www.testUrl.com"
            ),
            "bundle" => getenv("SDK_BUNDLE")
        );
    }

    // ========================================= [ DOMAIN ] =========================================

    public function testFindDomains()
    {
        $domains = $this->api->findDomains();
        $this->assertInternalType("array", $domains);
        return $domains[0];
    }

    public function testCreateDomain()
    {
        $created = $this->api->createDomain($this->domain, true);
        $this->assertArrayHasKey("name", $created);

        return $created["name"];
    }

    /**
     * @depends testCreateDomain
     */
    public function testFindDomainsWithFilter(string $name)
    {
        $domains = $this->api->findDomains("name=\"{$name}\"");
        $this->assertNotEmpty($domains);

        $domain = $domains[0];
        $this->assertArrayHasKey("name", $domain);
        return $domain;
    }

    /**
     * @depends testFindDomainsWithFilter
     */
    public function testUpdateDomain(array $fetched)
    {
        $fetched["scheme"] = "https";
        $fetched["deepScan"] = "https://www.testUrl.com";
        $fetched["fastScans"] = array(
            "https://www.testUrl.com"
        );

        $updated = $this->api->updateDomain($fetched["id"], $fetched);
        $this->assertEquals("https", $updated["scheme"]);

        return $updated;
    }

    /**
     * @depends testUpdateDomain
     */
    public function testDeleteDomain(array $updated)
    {
        $this->assertNull($this->api->deleteDomain($updated["id"]));
    }

    // ========================================= [ RESULT ] =========================================

    /**
     * @depends testFindDomains
     */
    public function testFindResults(array $domain)
    {
        $results = $this->api->findResults($domain["id"]);
        $this->assertInternalType("array", $results);
        return $results;
    }

    /**
     * @depends testFindDomains
     * @depends testFindResults
     */
    public function testFindSpecificResults(array $domain, array $results)
    {
        if (count($results) == 0) {
            $this->markTestSkipped("No results found for this domain");
        }

        $result = $this->api->findSpecificResult($domain["id"], $results[0]["id"]);
        $this->assertArrayHasKey("md5", $result);
    }

    // ========================================= [ APPLICATION ] =========================================

    /**
     * @depends testFindDomains
     */
    public function testFindApplications(array $domain)
    {
        $applications = $this->api->findApplications($domain["id"]);
        $this->assertInternalType("array", $applications);
    }

    /**
     * @depends testFindDomains
     */
    public function testFindValidApplications(array $domain)
    {
        $applications = $this->api->findApplications($domain["id"]);
        if (count($applications) == 0) {
            $this->markTestSkipped("No applications found for this domain");
        }

        $this->assertArrayHasKey("name", $applications[0]);
    }
}
