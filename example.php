<?php
	
	// -- PHP Example code demonstrating some method calls of the API client --

    require_once ('lib/NimbusecAPI.php');
    
    // -- Define credentials --
    $NIMBUSEC_KEY = '--- YOUR KEY ---';
    $NIMBUSEC_SECRET = '--- YOUR SECRET ---';
    
    // -- Create a new instance of the API client --
    $apiInstance = new NimbusecAPI ( $NIMBUSEC_KEY, $NIMBUSEC_SECRET );
    
    // -- List all domains --
    echo $apiInstance->findDomains ();
    
    // -- Search for a specific domain --
    $domains = $apiInstance->findDomains ( "name=\"www.nimbusec.com\"" );
    $domainID = $domains[0]['id'];
    
    // -- Find CMS concerned results for a specific domain --
    echo $apiInstance->findResults( $domainID, "event=\cms-vulnerable\"");
    
    // -- Create a new domain --
    $newDomain = array (
            "scheme" => "https",
            "name" => "www.somedomain.com",
            "deepScan" => "https://www.somedomain.com",
            "fastScans" => array (
                    "https://www.somedomain.com"
            ),
            "bundle" => "--- BUNDLE ID ---"
    );
    
    echo $apiInstance->createDomain ( $newDomain );
    
    // -- Search for a specific user --
    $users = $apiInstance->findUsers ( "login=\"someone@example.com\"" );
    $userID = $users[0]['id'];
    
    // -- Update the fetched user --
    $userUpdate = array (
            "company" => "Cumulo",
            "surname" => "Mustermann",
            "forename" => "Max"
    );
    
    echo $apiInstance->updateUser ( $userID, $userUpdate );
    
    // -- Delete the user --
    $apiInstance->deleteUser ( $userID );

?>