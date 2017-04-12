<?php

// include autoloader to load Nimbusec API automatically
require_once("vendor/autoload.php");

// write alias for Nimbusec API
use Nimbusec\API as API;

// set credentials
$NIMBUSEC_KEY = 'YOUR KEY';
$NIMBUSEC_SECRET = 'YOUR SECRET';

// create new Nimbusec API client
// the default url parameter can be omitted
$api = new API($NIMBUSEC_KEY, $NIMBUSEC_SECRET, API::DEFAULT_URL);

try {
    // fetch domains
    $domains = $api->findDomains();
    foreach ($domains as $domain) {
        echo $domain["name"] . "\n";
    }

    // find specific domain
    $domain = $api->findDomains("name=\"nimbusec.com\"")[0];
    echo "The id of nimbusec.com domain is: {$domain['id']}\n";

    // find all applications
    $applications = $api->findApplications($domain["id"]);

    $mapped = array_map(function ($application) {
        return "{$application['name']}: {$application['version']}";
    }, $applications);
    echo "All applications of nimbusec.com: [" . implode(", ", $mapped) . "]\n";

    // find results
    $results = $api->findResults($domain["id"]);
    echo "Number of results for nimbusec.com: ". count($results) . "\n";

    // create a new user
    $user = array(
        "login" => "john.doe@example.com",
        "mail" => "john.doe@example.com",
        "role" => "user",
        "forename" => "John",
        "surname" => "Doe"
    );
    $created = $api->createUser($user);
    echo "Created a new user with name {$created['forename']} {$created['surname']}\n";

    // update the user
    $created["forename"] = "Franz";
    $updated = $api->updateUser($created["id"], $created);
    echo "Now we have {$updated['forename']} {$updated['surname']}\n";

    // delete the previously created and updated user
    $api->deleteUser($updated["id"]);
    echo "He is gone\n";
} catch (Exception $e) {
    echo "[x] an error occured: {$e->getMessage()}\n";
}
