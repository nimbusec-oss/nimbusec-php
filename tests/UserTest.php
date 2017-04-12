<?php

use PHPUnit\Framework\TestCase;
use Nimbusec\API;

class UserTest extends TestCase
{
    private $api;
    private $user;
    private $domain;

    public function setUp()
    {
        $this->api = new API(
            getenv("SDK_KEY"),
            getenv("SDK_SECRET"),
            getenv("SDK_URL")
        );

        $this->user = array(
            "login" => "john.doe@example.com",
            "mail" => "john.doe@example.com",
            "role" => "user",
            "forename" => "John",
            "surname" => "Doe"
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

    // ========================================= [ USER ] =========================================

    public function testFindUsers()
    {
        $users = $this->api->findUsers();
        $this->assertInternalType("array", $users);
        return $users[0];
    }

    public function testCreateUser()
    {
        $created = $this->api->createUser($this->user, true);
        $this->assertArrayHasKey("role", $created);

        return $created["login"];
    }

    /**
     * @depends testCreateUser
     */
    public function testFindUsersWithFilter(string $login)
    {
        $users = $this->api->findUsers("login=\"{$login}\"");
        $this->assertNotEmpty($users);

        $user = $users[0];
        $this->assertArrayHasKey("login", $user);
        return $user;
    }

    /**
     * @depends testFindUsersWithFilter
     */
    public function testUpdateUser(array $fetched)
    {
        $fetched["mail"] = "example@mydomain.com";
        $fetched["forename"] = "Doe";
        $fetched["surname"] = "John";

        $updated = $this->api->updateUser($fetched["id"], $fetched);
        $this->assertEquals("example@mydomain.com", $updated["mail"]);

        return $updated;
    }

    // ========================================= [ USER CONFIGURATION ] =========================================

    /**
     * @depends testUpdateUser
     */
    public function testFindUserConfigurations(array $user)
    {
        $confs = $this->api->findUserConfigurations($user["id"]);
        $this->assertInternalType("array", $confs);
    }

    /**
     * @depends testUpdateUser
     */
    public function testSetUserConfiguration(array $user)
    {
        $conf = $this->api->setUserConfiguration($user["id"], "language", "de");
        $this->assertEquals("de", $conf);
        return $user;
    }

    /**
     * @depends testUpdateUser
     */
    public function testFindSpecificUserConfiguration(array $user)
    {
        $conf = $this->api->findSpecificUserConfiguration($user["id"], "language");
        $this->assertEquals("de", $conf);
        return $user;
    }

    /**
     * @depends testFindSpecificUserConfiguration
     */
    public function testDeleteUserConfiguration(array $user)
    {
        $this->assertNull($this->api->deleteUserConfiguration($user["id"], "language"));
    }

    // ========================================= [ USER DOMAIN SET ] =========================================
        
    /**
     * @depends testUpdateUser
     */
    public function testCreateDomain()
    {
        $created = $this->api->createDomain($this->domain, true);
        $this->assertArrayHasKey("name", $created);

        return $created;
    }

    /**
     * @depends testUpdateUser
     * @depends testCreateDomain
     */
    public function testCreateDomainSet($user, $domain)
    {
        $set = $this->api->createDomainSet($user["id"], $domain["id"]);
        $this->assertInternalType("array", $set);
        $this->assertNotEmpty($set);
        return array($user, $domain);
    }

    /**
     * @depends testCreateDomainSet
     */
    public function testDeleteFromDomainSet(array $args)
    {
        list($user, $domain) = $args;
        $this->assertNull($this->api->deleteFromDomainSet($user["id"], $domain["id"]));
        return $domain;
    }

    // ========================================= [ TEAR DOWN ] =========================================

    /**
     * @depends testDeleteFromDomainSet
     */
    public function testDeleteDomain(array $domain)
    {
        $this->assertNull($this->api->deleteDomain($domain["id"]));
    }

    /**
     * @depends testUpdateUser
     * @depends testDeleteUserConfiguration
     * @depends testDeleteDomain
     */
    public function testDeleteUser(array $updated)
    {
        $this->assertNull($this->api->deleteUser($updated["id"]));
    }
}
