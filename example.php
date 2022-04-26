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

    //===============================NOTIFICATIONS================================

    $notification = [
        "domain" => 12971,
        "user" => "86976ebd-1f4a-4748-7ac4-bb655fd0a7af",
        "transport" => "mail",
        "blacklist" => 1,
        "defacement" => 1,
        "malware" => 1
    ];
    
    $notification=$api->createNotification($notification);
    $notification=$api->getNotification($notification["id"]);

    $notification["malware"]=0;
    $notification=$api->updateNotification($notification["id"], $notification);
    // print_r($notification);

    $notifications = $api->getDomainNotifications(12971);
    print_r($notifications);

    $notification=$api->deleteNotification($notification["id"]);
    echo "nothing ever happened \n";

    $notifications = $api->listNotifications();
    //print_r($notifications);

    //===================================ISSUES===================================

    $issues=$api->listIssues();
    echo "number of issues: ".count($issues). "\n";

    $issue=$api->getIssue(938738);
    echo "issue found! id: " . $issue["id"] . " - " . $issue["status"] . "\n";

    $ihistory=$api->listIssueHistory();
    echo "issue history track record: " . count($ihistory);


    // TODO: get some alternativ issue statuses for testing 
    $issue=[
        "status"=> "pending",
        "comment"=> "iz updated meister!",
        "externalIds"=> null
    ];

    $issue=$api->updateIssue(938738, $issue);
    echo "no error is tested enough! \n";
    

    
    //====================================PING====================================

    echo $api->ping()["message"] . "\n";

    //===================================BUNDLES===================================

    $bundles=$api->listBundles();
    foreach($bundles as $bundle){
        print_r($bundle["name"]);
    }

    $bundle=$api->getBundle($bundles[0]["id"]);
    // echo "take it back to the top of the stack, cause we got a working get: " . $bundle["name"]; 

    //===================================DOMAIN===================================

    // Create domain
    $domain=[
        "name"=>"crazynewnamethatstandsoutasd.yo",
    ];
    $domain = $api->createDomain($domain,true);
    echo "added {$domain['name']} to domains\n";

    // Read domain
    // $domainID = $domain["id"];
    // $domain = $api->getDomain(13182);
    // echo "found it's ID:" . $domain["id"];

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

    //===================================STATS====================================

    $stats = $api->listStats();
    print_r($stats);

} catch (Exception $e) {
    echo "[x] an error occured: {$e->getMessage()}\n";
}
