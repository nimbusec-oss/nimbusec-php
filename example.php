<?php

// Include autoloader to load Nimbusec API automatically.
require_once("vendor/autoload.php");

// Set an alias for Nimbusec API.
use Nimbusec\API as API;

// Set credentials.
$NIMBUSEC_KEY = "YOUR KEY";
$NIMBUSEC_SECRET = "YOUR SECRET";

// Create a Nimbusec API client instance.
// The default URL parameter may be omitted.
//
// The last parameter marks Guzzle options as described on: http://docs.guzzlephp.org/en/stable/request-options.html 
// By passing options, the default options we set for the client can be extended by e.g proxy features.
// The options can be passed as a variable, otherwise be left empty. Please note: in order to use the options, the URL parameter must be passed.
$options = [
    "timeout" => 30, 
    "proxy" => [
        "http"  => "tcp://localhost:8125",
    ],
];

$api = new API($NIMBUSEC_KEY, $NIMBUSEC_SECRET, API::DEFAULT_URL, $options);

try {
    // Fetch domains.
    $domains = $api->findDomains();
    foreach ($domains as $domain) {
        echo $domain["name"] . "\n";
    }

    // Find specific domain.
    $domain = $api->findDomains("name=\"nimbusec.com\"")[0];
    echo "The id of nimbusec.com domain is: {$domain['id']}\n";

    // Find all applications.
    $applications = $api->findApplications($domain["id"]);

    $mapped = array_map(function ($application) {
        return "{$application['name']}: {$application['version']}";
    }, $applications);
    echo "All applications of nimbusec.com: [" . implode(", ", $mapped) . "]\n";

    // Find results.
    $results = $api->findResults($domain["id"]);
    echo "Number of results for nimbusec.com: " . count($results) . "\n";

    // Find infected domains with a webshell
    $infected = $api->findInfected("event=\"webshell\"");
    echo "Number of infected domains: " . count($infected) . "\n";

    // Create a new user.
    $user = [
        "login" => "john.doe@example.com",
        "mail" => "john.doe@example.com",
        "role" => "user",
        "forename" => "John",
        "surname" => "Doe"
    ];
    $created = $api->createUser($user);
    echo "Created a new user with name {$created['forename']} {$created['surname']}\n";

    // Update the user.
    $created["forename"] = "Franz";
    $updated = $api->updateUser($created["id"], $created);
    echo "Now we have {$updated['forename']} {$updated['surname']}\n";

    // Delete the previously created and updated user.
    $api->deleteUser($updated["id"]);
    echo "He is gone\n";
} catch (Exception $e) {
    echo "[x] an error occured: {$e->getMessage()}\n";
}
