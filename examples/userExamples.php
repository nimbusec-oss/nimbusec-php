<?php
// Include autoloader to load Nimbusec API automatically.
require_once("../vendor/autoload.php");

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
	//===================================USER====================================
	echo "\n----USER----\n";

	echo "list of users: \n";
	// list all domains
	$users = $api->listUsers();
	foreach ($users as $user) {
		echo $user["forename"] . $user["surname"] . "\n";
	}
	echo "-END-\n";

	// testing with user at index 0
	$userID = $users[0]["id"];

	// create user
	$user = [
		"login" => "tester",
		"mail" => "test@nimbusec.com",
		"role" => "user"
	];
	$user = $api->createUser($user, true);
	echo "added {$user["login"]} to domains \n";

	// get a user by id
	$user = $api->getUser($user["id"]);
	echo "found their userID: " . $user["id"] . "\n";

	// update user
	$userNameOld = $user["forename"];
	$user["forename"] = "Max";
	$user = $api->updateUser($user["id"], $user);
	echo "updated their forname: \"{$userNameOld}\"  to: {$user['forename']}\n";

	// delete user
	$api->deleteUser($user["id"]);
	echo "deleted \"{$user['forename']}\" \n";


	//==============================USER-DOMAIN-SET===============================
	echo "\n----USER-DOMAIN-SET----\n";

	// needed for update below
	$domainSet = [
		"domains" => []
	];

	// list all domains
	$domains = $api->listUserDomainSet($userID);
	echo "list of domains for user {$userID}: \n";
	foreach ($domains as $domain) {
		echo $domain["name"] . "\n";
		array_push($domainSet["domains"], $domain["id"]);
	}
	echo "-END-\n";

	// testing with domain at user domain set index 0 
	$domainID = $domains[0]["id"];

	// remove domain from user domain set
	$api->removeDomainFromUserDomainSet($userID, $domainID);
	echo "removed domain {$domainID} from user {$userID} domain set\n";

	// add domain to user domain set
	$api->addDomainToUserDomainSet($userID, $domainID);
	echo "added domain {$domainID} to user {$userID} domain set\n";

	// updates domain set of user with matching id
	$api->updateUserDomainSet($userID, $domainSet);
	echo "updated domainSet for user {$userID}\n";


	//=============================USER-CONFIGURATION==============================
	echo "\n----USER-CONFIGURATION----\n";

	// TODO: only users with a valid cBundle can access configuration files, insert an eligible users ID below to access their configs
	$userID = "";

	echo "list of configs: for user {$userID}\n";
	// list all domains
	$configs = $api->listUserConfigs($userID);
	foreach ($configs as $config) {
		echo "{$config["key"]}: {$config["value"]}";
	}
	echo "-END-\n";

	// testing with config at index 0
	$configID=$configs[0]["key"];

	// get a config by id
	$config = $api->getUserConfig($userID, $configID);
	echo "found the config: {$config["key"]}: {$config["value"]}\n";

	// update config
	$configValueOld=$config["value"];
	$config["value"]="true";
	$config=$api->updateUserConfig($userID,$configID, $config);
	echo "updated user config {$config["key"]}'s value from: {$configValueOld}  to: + {$config["value"]}\n" ;
	// revert update
	$config["value"]=$configValueOld;
	$config=$api->updateUserConfig($userID,$configID, $config);
	echo "reverted user config {$config["key"]}'s value to: {$config["value"]}\n" ;

	//TODO: uncomment in order to delete a config 
	// delete config
	// $api->deleteUserConfig($userID, $configID);
	// echo "deleted config\n";

	//=============================USER-FAVORITES==============================
	echo "\n----USER-FAVORITES----\n";

	// needed for update below
	$userFavorites = [
		"domains" => []
	];

	// list all domains
	$domains = $api->listUserFavorites($userID);
	echo "list of favorite domains for user {$userID}: \n";
	foreach ($domains as $domain) {
		echo $domain["name"] . "\n";
		array_push($userFavorites["domains"], $domain["id"]);
	}
	echo "-END-\n";

	// further testing with domain at user favorites index 0 
	$domainID = $domains[0]["id"];

	// remove domain from user favorites
	$api->removeDomainFromUserFavorites($userID, $domainID);
	echo "removed domain {$domainID} from user {$userID} favorites\n";

	// add domain to user favorites
	$api->addDomainToUserFavorites($userID, $domainID);
	echo "added domain {$domainID} to user {$userID} favorites\n";

	// updates favorite domains of user with matching id
	$api->updateUserFavorites($userID, $userFavorites);
	echo "updated favorites for user {$userID}\n";
} catch (Exception $e) {
	echo "[x] an error occured: {$e->getMessage()}\n";
}
