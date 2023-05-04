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
	//====================================PING=====================================
	echo "\n-----Ping-----\n";

	// check if connection to api can be established
	echo $api->ping()["message"] . "\n";

	//===================================BUNDLES===================================
	echo "\n----Bundles----\n";

	// list all bundles
	$bundles = $api->listBundles();
	echo "bundles: ";
	foreach ($bundles as $bundle) {
		echo $bundle["name"] . " ";
	}
	echo "\n";

	// get bundle by id
	if (count($bundles) != 0) {
		$bundle = $api->getBundle($bundles[0]["id"]);
		echo "bundle at index 0: " . $bundle["name"];
	}
	echo "\n";
} catch (Exception $e) {
	echo "[x] an error occured: {$e->getMessage()}\n";
}
