<?php
// Include autoloader to load Nimbusec API automatically.
require_once("vendor/autoload.php");

// Set an alias for Nimbusec API.
use Nimbusec\API as API;

// Set credentials.
$NIMBUSEC_KEY = "";
$NIMBUSEC_SECRET = "";

// Create a Nimbusec API client instance.
// The default URL parameter may be omitted.
//
// The last parameter marks Guzzle options as described on: http://docs.guzzlephp.org/en/stable/request-options.html 
// By passing options, the default options we set for the client can be extended by e.g proxy features.
// The options can be passed as a variable, otherwise be left empty. Please note: in order to use the options, the URL parameter must be passed.
// $options = [
//     "timeout" => 30,
//     "proxy" => [
//         "http"  => "tcp://localhost:8125",
//     ],
// ];

$options = [
    'max'             => 5,
    'strict'          => false,
    'referer'         => false,
    'protocols'       => ['http', 'https'],
    'track_redirects' => false
];

const DEV_URL = "https://api-dev.nimbusec.com";

$api = new API($NIMBUSEC_KEY, $NIMBUSEC_SECRET, DEV_URL, $options);

try {
	//====================================PING=====================================

	echo "\n-----Ping-----\n";
	// check if connection to api can be established
	echo $api->ping()["message"] . "\n";

	//===================================BUNDLES===================================

	echo "\n----Bundles----\n";
	// list all bundles
	$bundles=$api->listBundles();
	echo "bundles: ";
	foreach($bundles as $bundle){
		echo $bundle["name"] . " ";
	}
	echo "\n";

	// get bundle by id
	if(count($bundles)!=0) {
		$bundle=$api->getBundle($bundles[0]["id"]);
		echo "bundle at index 0: " . $bundle["name"]; 
	}
	echo "\n";

	
	//===================================ISSUES==================================

	echo "\n--Issues--\n";

	// get all issues  
	$issues=$api->listIssues();
	echo "number of issues: ".count($issues). "\n";

	// testing with issue at index 0
	$issueID=$issues[0]["id"];

	// find issue by id
	$issue=$api->getIssue($issueID);
	echo "issue found! id: " . $issue["id"] . " - " . $issue["status"] . "\n";

	// get issue history
	$ihistory=$api->listIssueHistory();
	echo "issue history track record: " . count($ihistory) . "\n";

	// update an issue | status: [pending, acknowledged, ignored]
	$issue=[
			"status"=> "pending",
			"comment"=> "iz updated meister!",
			"externalIds"=> null
	];
	$issue=$api->updateIssue($issueID, $issue);
	echo "issue has been updated! status: ". $issue["status"] ."\n";


	//===================================TOKENS===================================
	echo "\n----TOKENS----\n";

	echo "list of tokens: \n";
	// list all domains
	$tokens = $api->listTokens();
	foreach ($tokens as $token) {
			echo $token['name'] . "\n";
	}
	echo "-END-\n";

	// create token
	$token=[
		"name"=>"newToken",
		"role"=>"agent",//eligible roles: agent, readonly
		"lastVersion"=> 0
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
