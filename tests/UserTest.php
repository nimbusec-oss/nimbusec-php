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
            "role" => "admin",
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

    /**
     * @depends testUpdateUser
     */
    public function testDeleteUser(array $updated)
    {
        $this->assertNull($this->api->deleteUser($updated["id"]));
    }
}
