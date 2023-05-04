<?php
// Include autoloader to load Nimbusec API automatically.
require_once("vendor/autoload.php");

// Set an alias for Nimbusec API.
use Nimbusec\API as API;

// Set credentials.
$NIMBUSEC_KEY = "";
$NIMBUSEC_SECRET = "";

$options = [
	'max'             => 5,
	'strict'          => false,
	'referer'         => false,
	'protocols'       => ['http', 'https'],
	'track_redirects' => false
];

const DEV_URL = "https://api-dev.nimbusec.com";

$api = new API($NIMBUSEC_KEY, $NIMBUSEC_SECRET, DEV_URL, $options);

// !!! Mind that these examples do naturally change entries in the database, it is recommended to use them as a reference only !!!

try {
	//==================================AGENT===================================
	echo "\n---AGENT---\n";

	// list all agents
	$agents = $api->listAgents();
	echo "list of agents: \n";
	foreach ($agents as $agent) {
		echo $agent["url"];
	}
	echo "-END-\n";

	// further testing with agent at index 0
	$a = $agents[0];

	// get a specific agent 
	$agent = $api->findSpecificAgent($a["os"], $a["arch"], $a["version"], $a["format"]);
	file_put_contents("agent." . $a["format"], $agent);

	echo "downloaded agent -> agent.{$a["format"]} \n";

	//===============================AGENT-TOKENS===============================
	echo "\n----AGENT-TOKENS----\n";

	// list all domains
	$tokens = $api->listTokens();
	echo "list of tokens: \n";
	foreach ($tokens as $token) {
		echo $token['name'] . "\n";
	}
	echo "-END-\n";

	// create token
	$token = [
		"name" => "newToken",
		"role" => "agent", //eligible roles: agent, readonly
		"lastVersion" => 0
	];
	$token = $api->createToken($token);
	echo "created token: {$token['name']} \n";

	// get a token by id
	$token = $api->getToken($token["key"]);
	echo "found it's ID/key: " . $token["key"] . "\n";

	// delete token
	$api->deleteToken($token["key"]);
	echo "deleted {$token['name']}\n";
} catch (Exception $e) {
	echo "[x] an error occured: {$e->getMessage()}\n";
}
