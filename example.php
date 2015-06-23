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
    
    // -- Get a specific domain --
    echo $apiInstance->findDomains ( "name=\"www.nimbusec.com\"" );
    
    // -- Create a new domain --
    $domain = array (
            "scheme" => "https",
            "name" => "www.somedomain.com",
            "deepScan" => "https://www.somedomain.com",
            "fastScans" => array (
                    "https://www.somedomain.com"
            ),
            "bundle" => "--- BUNDLE ID ---"
    );
    
    echo $apiInstance->createDomain ( $domain );
    
    // -- Get id of a specific user --
    $user = $apiInstance->findUsers ( "login=\"someone@example.com\"" );
    $id = $user['id'];
    
    // -- Update the fetched user --
    $userUpdate = array (
            "company" => "Cumulo",
            "surname" => "Mustermann",
            "forename" => "Max"
    );
    
    echo $apiInstance->updateUser ( $id, $userUpdate );
    
    // -- Delete the user --
    $apiInstance->deleteUser ( $id );

?>