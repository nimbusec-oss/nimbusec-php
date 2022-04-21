<?php

// Include autoloader to load Nimbusec API automatically.
require_once("vendor/autoload.php");

// Set an alias for Nimbusec API.
use Nimbusec\API as API;

// Set credentials.
$NIMBUSEC_KEY = "FkFTtI3QMX6t2U5ZzYHVSXxRyr7QcJ9e";
$NIMBUSEC_SECRET = "VvZkEDCArOF5Y5jD5UcSkAbDy9zSob0b";

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

    //====================================PING====================================

    echo $api->ping()["message"] . "\n";

    //===================================DOMAIN===================================

    // Create domain
    $domain=[
        "name"=>"crazynewnamethatstandsout.yo",
    ];
    $domain = $api->createDomain($domain,false);
    echo "added {$domain['name']} to domains\n";

    // Read domain
    $domainID = $domain["id"];
    $domain = $api->getDomain($domainID);
    echo "found it's ID:" . $domain["id"];

    // Update domain
    $domainNameOld=$domain["name"];
    $domain["name"]="{$domainNameOld}new";
    $domain=$api->updateDomain($domain["id"],$domain);
    echo "updated domain: {$domainNameOld}  to: + {$domain['name']}\n" ;

    // Delete domain
    $api->deleteDomain($domain["id"]);
    echo "del {$domain['id']}\n";

    // List all domains
    $domains = $api->findDomains();
    foreach ($domains as $domain) {
        echo $domain["name"] . "\n";
    }

    //==================================METDATA===================================

    $metadata=$api->listDomainMetadata();
    foreach ($metadata as $m) {
        echo $m["domain"] . "\n";
        // use print_r to print full metadata
        // print_r($m);
    }

    $metadata=$api->getDomainMetadata(13054);
    echo "specific metadata found for: ".$metadata["domain"] . "\n";
    // use print_r to print full metadata
    // print_r($m);

    //==================================STATS====================================

    $stats = $api->listStats();
    print_r($stats);



    // Find results.
    // $results = $api->findResults($domain["id"]);
    // echo "Number of results for nimbusec.com: " . count($results) . "\n";

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
