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
	//====================================ISSUES===================================
	echo "\n--Issues--\n";

	// get all issues  
	$issues = $api->listIssues();
	echo "number of issues: " . count($issues) . "\n";

	// testing with issue at index 0
	$issueID = $issues[0]["id"];

	// find issue by id
	$issue = $api->getIssue($issueID);
	echo "issue found! id: " . $issue["id"] . " - " . $issue["status"] . "\n";

	// get issue history
	$ihistory = $api->listIssueHistory();
	echo "issue history track record: " . count($ihistory) . "\n";

	// update an issue | status: [pending, acknowledged, ignored]
	$issue = [
		"status" => "pending",
		"comment" => "issue is pending",
		"externalIds" => null
	];
	$issue = $api->updateIssue($issueID, $issue);
	echo "issue has been updated! status: " . $issue["status"] . "\n";
} catch (Exception $e) {
	echo "[x] an error occured: {$e->getMessage()}\n";
}
