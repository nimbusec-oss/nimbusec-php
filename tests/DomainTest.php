<?php

use PHPUnit\Framework\TestCase;
use Nimbusec\API;

class DomainTest extends TestCase {
	private $api;
	private $bundle;

	public function setUp() {
		$this->api = new API (
			getenv("SDK_KEY"),
			getenv("SDK_SECRET"),
			getenv("SDK_URL")
		);

		$this->bundle = getenv("SDK_BUNDLE");
	}

	public function testFind() {
		$domains = $this->api->findDomains();
		$this->assertTrue($domains !== NULL);
	}

	public function testCreate() {
		$domain = array (
			"scheme" => "http",
			"name" => "www.randomurl.com",
			"deepScan" => "http://www.randomurl.com",
			"fastScans" => array (
					"http://www.randomurl.com"
			),
			"bundle" => $this->bundle
		);

		$created = $this->api->createDomain($domain);
		$this->assertArrayHasKey("name", $created);

		return $created["name"];
	}

	/**
	 * @depends testCreate
	 */
	public function testFindWithFilter(string $name) {
		$domains = $this->api->findDomains("name=\"{$name}\"");
		$this->assertNotEmpty($domains);

		$domain = $domains[0];
		$this->assertArrayHasKey("name", $domain);
		return $domain;
	}

	/**
	 * @depends testFindWithFilter
	 */
	public function testUpdate(array $fetched) {
		$fetched["scheme"] = "https";
		$fetched["deepScan"] = "https://www.randomurl.com";
		$fetched["fastScans"] = array (
			"https://www.randomurl.com"
		);

		$updated = $this->api->updateDomain($fetched["id"], $fetched);
		$this->assertEquals("https", $updated["scheme"]);

		return $updated;
	}

	/**
	 * @depends testUpdate
	 */
	public function testDelete(array $updated) {
		$this->assertEquals(NULL, $this->api->deleteDomain($updated["id"]));
	}
}