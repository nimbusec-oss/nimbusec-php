<?php

use PHPUnit\Framework\TestCase;
use Nimbusec\API;

class AgentTest extends TestCase
{
    private $api;
    private $token;
    private $agentVer;

    public function setUp()
    {
        $this->api = new API(
            getenv("SDK_KEY"),
            getenv("SDK_SECRET"),
            getenv("SDK_URL")
        );

        $this->token = array(
            "name" => "testToken"
        );

        $this->agentVer = getenv("SDK_AGENT_VERSION");
    }

    // ========================================= [ AGENT TOKEN ] =========================================

    public function testFindAgentToken()
    {
        $token = $this->api->findAgentToken();
        $this->assertInternalType("array", $token);
        return $token[0];
    }

    /**
     * @depends testFindAgentToken
     */
    public function testFindValidAgentToken($token)
    {
        $this->assertArrayHasKey("key", $token);
    }

    public function testCreateAgentToken()
    {
        $created = $this->api->createAgentToken($this->token);
        $this->assertArrayHasKey("key", $created);
        return $created;
    }

    /**
     * @depends testCreateAgentToken
     */
    public function testDeleteAgentToken($token)
    {
        $this->assertEquals(null, $this->api->deleteAgentToken($token["id"]));
    }

    // ========================================= [ AGENT ] =========================================

    public function testFindServerAgents()
    {
        $agents = $this->api->findServerAgents();
        $this->assertInternalType("array", $agents);
        return $agents[0];
    }

    /**
     * @depends testFindServerAgents
     */
    public function testFindValidServerAgents($agent)
    {
        $this->assertArrayHasKey("arch", $agent);
    }

    public function testFindSpecificServerAgent()
    {
        $agent = $this->api->findSpecificServerAgent("linux", "64bit", $this->agentVer, "tar.gz");
        $this->assertNotNull($agent);
    }
}
