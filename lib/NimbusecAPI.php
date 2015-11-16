<?php
require_once ('OAuth.php');
require_once ('CURLClient.php');

/**
 * The official Nimbusec API client written in PHP. 
 * It uses a custom Client URL Library (cURL) to communicate with the Nimbusec API via SSL and the OAuth standard to get the required authentification.
 * 
 * The API Client covers typical CRUD operations (RESTful concept) to interact with the following objects:
 * <ul>
 *      <li>Domains</li><li>Bundles</li><li>Users</li><blockquote><li>Notifications</li><li>DomainSets</li></blockquote><li>Agents</li><blockquote><li>Agent Tokens</li></blockquote>
 * </ul>
 * 
 * All objects being passed as method parameters must be non-JSON associative arrays as they'll be encoded / decoded and later on send to the server within the method. 
 * Otherwise an exception from type NimbusecException will be thrown. 
 * 
 * Please note that this API client may not be complete as for the possible operations described in the Nimbusec API documentation. The API client will be expanded when additional features are needed.
 * 
 * Default server URL is "https://api.nimbusec.com"
 * 
 * <u> See the documentation for more details. </u>
 */
class NimbusecAPI {
    
    // -- Contains the cURL instance used to send the request to the API via SSL --
    private $client;
    
    // -- OAuth consumer --
    private $consumer;
    
    // -- Default server URL being used if not passed explicitly upon instantiating --
    private $DEFAULT_BASE_URL = "https://api.nimbusec.com";
    
    /**
     * The constructor of the Nimbusec API client
     * 
     * @param string $key - A valid Nimbusec API Key
     * @param string $secret - A valid Nimbusec API Secret
     * @param string $BASE_URL - <b><i>Optional:</i></b> defines the Nimbusec base URL. When not passed, the default URL will be used
     */
    function __construct ( $key, $secret, $BASE_URL = null ) {
        
        // -- If no URL was passed, use default --
        if ( !empty ( $BASE_URL ) )
            $this->DEFAULT_BASE_URL = $BASE_URL;
            
        // -- Create an OAuth consumer based on the given credentials --
        $this->consumer = new OAuthConsumer ( $key, $secret );
        
        // -- Create new cURL instance --
        $this->client = new CURLClient ();
    }

    /**
     * Create a domain from the given object.
     *
     * @param array $domain - The array of the domain to be created
     * @throws NimbusecException When an error occurs during JSON encoding / decoding; <br/>Contains the <b>JSON error message</b>.
     * @return array - The created domain object
     */
    function createDomain ( $domain ) {

        $payload = json_encode ( $domain );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error ocurred '{$err}' while encoding" );
            
        // -- Domain base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/domain";
        
        // -- Create OAuth request based on OAuth consumer and the specific URL --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'POST', $url );
        
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        $response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl, null, $payload );
        
        $domain = json_decode ( $response, true );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error occured '{$err}' while trying to decode {$response}" );
        else
            return $domain;
    }

    /**
     * Read all existing domains depending on an optional filter.
     *
     * @param string $filter - Defines the field + value to be filtered by. Filter format: <b>field="value"</b>.<br /><i>NOTE: the filter can be
     * missing or left blank.</i>
     * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>JSON error message</b>.
     * @return array - A nested array containing all domain objects
     */
    function findDomains ( $filter = null ) {
        
        // -- Domain base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/domain";
        
        // -- Create OAuth request based on OAuth consumer and the specific URL --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'GET', $url );
        
        // -- Check if filter was passed; if so, append to params --
        if ( !empty ( $filter ) )
            $request->set_parameter ( 'q', $filter );
            
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        $response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
        
        $domains = json_decode ( $response, true );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error occured '{$err}' while trying to decode {$response}" );
        else
            return $domains;
    }

    /**
     * Update an existing domain by the given object. To modify only certain fields of the domain you can include just these fields inside of the domain object you pass. 
     * The destination path for the request is determined by the ID.
     *
     * @param int $domainID - The domain's assigned ID (must be valid)
     * @param array $domain - The domain object with the fields to be updated
     * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>JSON error message</b>.
     * @return array - The updated domain object
     */
    function updateDomain ( $domainID, $domain ) {

        $payload = json_encode ( $domain );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error ocurred '{$err}' while encoding" );
        
        // -- Domain base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/domain/" . $domainID;
        
        // -- Create OAuth request based on OAuth consumer and the specific URL --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'PUT', $url );
        
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        $response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl, null, $payload );
        
        $domain = json_decode ( $response, true );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error occured '{$err}' while trying to decode {$response}" );
        else
            return $domain;
    }

    /**
     * Delete a specific domain.
     * The destination path for the request is determined by the ID.
     * 
     * No return value.
     * 
     * @param int $domainID - The domain's assigned ID (must be valid)
     */
    function deleteDomain ( $domainID ) {

        // -- Domain base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/domain/" . $domainID;
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'DELETE', $url );
        
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cURL request --
        // -- NOTE: This request would basically return nothing, but as the empty HTTP Response body string will be cut off from the header string
        //    through "substr" which for its part fails it returns boolean false --
        $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
    }

    /**
     * Read all existing bundles depending on an optional filter.
     *
     * @param string $filter - Defines the field + value to be filtered by. Filter format: <b>field="value"</b>.<br /><i>NOTE: the filter can be
     * missing or left blank.</i>
     * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>JSON error message</b>.
     * @return array - A nested array containing all bundle objects
     */
    function findBundles ( $filter = null ) {

        // -- Bundle base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/bundle";
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'GET', $url );
        
        if ( !empty ( $filter ) )
            $request->set_parameter ( 'q', $filter );
            
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        $response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
        
        $bundles = json_decode ( $response, true );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error occured '{$err}' while trying to decode {$response}" );
        else
            return $bundles;
    }

    /**
     * Create a user from the given object.
     *
     * @param array $user - The array of the user to be created
     * @throws NimbusecException When an error occurs during JSON encoding / decoding; <br/>Contains the <b>JSON error message</b>.
     * @return array - The created user object
     */
    function createUser ( $user ) {

        $payload = json_encode ( $user );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error ocurred '{$err}' while encoding" );
        
        // -- User base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/user";
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'POST', $url );
        
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        $response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl, null, $payload );
        
        $user = json_decode ( $response, true );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error occured '{$err}' while trying to decode {$response}" );
        else
            return $user;
    }

    /**
     * Read all existing users depending on an optional filter.
     *
     * @param string $filter - Defines the field + value to be filtered by. Filter format: <b>field="value"</b>.<br /><i>NOTE: the filter can be
     * missing or left blank.</i>
     * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>JSON error message</b>.
     * @return array - A nested array containing all user objects
     */
    function findUsers ( $filter = null ) {

        // -- User base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/user";
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'GET', $url );
        
        // -- Check if filter was passed; if so, append to params --
        if ( !empty ( $filter ) )
            $request->set_parameter ( 'q', $filter );
            
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        $response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
        
        $users = json_decode ( $response, true );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error occured '{$err}' while trying to decode {$response}" );
        else
            return $users;
    }

    /**
     * Update an existing user by the given object. To modify only certain fields of the user you can include just these fields inside of the user object you pass.
     * The destination path for the request is determined by the ID.
     *
     * @param int $userID - The user's assigned ID (must be valid)
     * @param array $user - The user object with the fields to be updated
     * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>JSON error message</b>.
     * @return array - The updated user object
     */
    function updateUser ( $userID, $user ) {

        $payload = json_encode ( $user );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error ocurred '{$err}' while encoding" );
        
        // -- User base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/user/" . $userID;
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'PUT', $url );
        
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        $response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl, null, $payload );
        
        $user = json_decode ( $response, true );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error occured '{$err}' while trying to decode {$response}" );
        else
            return $user;
    }

    /**
     * Delete a specific user.
     * The destination path for the request is determined by the ID.
     *  
     * No return value.
     * 
     * @param int $userID - The user's assigned ID (must be valid)
     */
    function deleteUser ( $userID ) {

        // -- User base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/user/" . $userID;
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'DELETE', $url );
        
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        // -- NOTE: This request would basically return nothing, but as the empty HTTP Response body string will be cut off from the header string
        //    through "substr" which for its part fails it returns boolean false --
        $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
    }
    
    function findUserConfigurations( $userID ){
        
        // -- User Configuration base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/user/" . $userID . "/config";
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'GET', $url );
        
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        $response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
        
        $userConfigs = json_decode ( $response, true );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error occured '{$err}' while trying to decode {$response}" );
        else
            return $userConfigs;
    }
    
    function findSpecificUserConfiguration( $userID, $key ) {
        
        // -- User Configuration base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/user/" . $userID . "/config/" . $key . "/";
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'GET', $url );
        
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        $response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
        
        return $response;
    }
    
    function createUserConfiguration( $userID, $key, $value ) {
    
        $payload = json_encode ( $value );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error ocurred '{$err}' while encoding" );
        
        // -- User Configuration base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/user/" . $userID . "/config/" . $key . "/";
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'PUT', $url );
        
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        $response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl, null, $payload );
        
        $user = json_decode ( $response, true );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error occured '{$err}' while trying to decode {$response}" );
        else
            return $user;
    }
    
    function deleteUserConfiguration( $userID, $key ) {
    
        // -- User Configuration base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/user/" . $userID . "/config/" . $key . "/";
    
       // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'DELETE', $url );
        
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        // -- NOTE: This request would basically return nothing, but as the empty HTTP Response body string will be cut off from the header string
        //    through "substr" which for its part fails it returns boolean false --
        $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
    }

    /**
     * Create a notification from the given object for a certain user.
     *
     * @param array $notification - The array of the notification to be created
     * @param int | string $userID - The user's assigned ID (must be valid)
     * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>JSON error message</b>.
     * @return array - The created notification object
     */
    function createNotification ( $notification, $userID ) {

        $payload = json_encode ( $notification );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error ocurred '{$err}' while encoding" );
        
        // -- Notification base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/user/{$userID}/notification";
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'POST', $url );
        
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        $response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl, null, $payload );
        
        $notification = json_decode ( $response, true );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error occured '{$err}' while trying to decode {$response}" );
        else
            return $notification;
    }

    /**
     * Read all existing notifications of a certain user depending on an optional filter.
     *
     * @param int | string $userID - The user's assigned ID (must be valid)
     * @param string $filter - Defines the field + value to be filtered by. Filter format: <b>field="value"</b>.<br /><i>NOTE: the filter can be
     * missing or left blank.</i>
     * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>JSON error message</b>.
     * @return array - A nested array containing all notification objects
     */
    function findNotifications ( $userID, $filter = null ) {

        // -- Notification base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/user/{$userID}/notification";
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'GET', $url );
        
        // -- Check if filter was passed; if so, append to params --
        if ( !empty ( $filter ) )
            $request->set_parameter ( 'q', $filter );
            
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        $response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
        
        $notifications = json_decode ( $response, true );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error occured '{$err}' while trying to decode {$response}" );
        else
            return $notifications;
    }

    /**
     * Add a certain domain to a certain user.
     *
     * @param int | string $userID - The user's assigned id (must be valid)
     * @param int | string $domainID - The domain's assigned id (must be valid)
     * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>JSON error message</b>.
     * @return array - A nested array containing a list of domain id's
     */
    function createDomainSet ( $userID, $domainID ) {

        // -- User base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/user/" . $userID . "/domains";
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'POST', $url );
        
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        $response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl, NULL, $domainID );
        
        $domainSet = json_decode ( $response, true );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error occured '{$err}' while trying to decode {$response}" );
        else
            return $domainSet;
    }

    /**
     * Read all assigned domains of a certain user.
     *
     * @param int | string $userID - The user's assigned id
     * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>JSON error message</b>.
     * @return array - A nested array containing a list of domain id's
     */
    function findDomainSet ( $userID ) {

        // -- User base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/user/" . $userID . "/domains";
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'GET', $url );
        
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        $response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
        
        $domainSet = json_decode ( $response, true );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error occured '{$err}' while trying to decode {$response}" );
        else
            return $domainSet;
    }

    /**
     * Remove a specific domain from a certain user.
     * 
     * No return value.
     *
     * @param int | string $userID - The user's assigned id (must be valid)
     * @param int | string $domainID - The domain's assigned id (must be valid)
     * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>JSON error message</b>.
     */
    function deleteFromDomainSet ( $userID, $domainID ) {

        // -- User base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/user/" . $userID . "/domains/" . $domainID;
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'DELETE', $url );
        
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        // -- NOTE: This request would basically return nothing, but as the empty HTTP Response body string will be cut off from the header string
        //    through "substr" which for its part fails it returns boolean false --
        $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
    }

    /**
     * Read all existing serveragents depending on an optional filter.
     * 
     * @param string $filter - Defines the field + value to be filtered by. Filter format: <b>field="value"</b>.<br /><i>NOTE: the filter can be
     * missing or left blank.</i>
     * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>JSON error message</b>.
     * @return array - A nested array containing all serveragent objects
     */
    function findServerAgents ( $filter = null ) {

        // -- Serveragent base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/agent/download";
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'GET', $url );
        
        // -- Check if filter was passed; if so, append to params --
        if ( !empty ( $filter ) )
            $request->set_parameter ( 'q', $filter );
            
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        $response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
        
        $serverAgents = json_decode ( $response, true );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error occured '{$err}' while trying to decode {$response}" );
        else
            return $serverAgents;
    }

    /**
     * Read a specific serveragent by given parameters.
     * 
     * @param string $os - Operating system of agent (windows, macosx, linux)
     * @param string $arch - CPU architecture of agent (32bit, 64bit)
     * @param string $version - Version of agent
     * @param string $type - Format of downloaded file (zip, bin). Default value is zip.
     * @return string - Depending on the given format, the server agent as string
     */
    function findSpecificServerAgent ( $os, $arch, $version, $type = "zip" ) {

        $url = $this->DEFAULT_BASE_URL . "/v2/agent/download/nimbusagent-{$os}-{$arch}-{$version}.{$type}";
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'GET', $url );
        
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        $response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
        
        return $response;
    }

    /**
     * Create an server agent token from the given object.
     * In the following step this token can be used to run the server agent.
     *
     * @param array $token - The array of the token to be created
     * @throws NimbusecException When an error occurs during JSON encoding / decoding; <br/>Contains the <b>JSON error message</b>.
     * @return array - The created token object
     */
    function createAgentToken ( $token ) {

        $payload = json_encode ( $token );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error ocurred '{$err}' while encoding" );
        
        // -- Token base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/agent/token";
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'POST', $url );
        
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        $response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl, null, $payload );
        
        $token = json_decode ( $response, true );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error occured '{$err}' while trying to decode {$response}" );
        else
            return $token;
    }

    /**
     * Read all existing tokens depending on an optional filter.
     *
     * @param string $filter - Defines the field + value to be filtered by. Filter format: <b>field="value"</b>.<br /><i>NOTE: the filter can be
     * missing or left blank.</i>
     * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>JSON error message</b>.
     * @return array - A nested array containing all token objects
     */
    function findAgentToken ( $filter = null ) {

        // -- Token base path --
        $url = $this->DEFAULT_BASE_URL . "/v2/agent/token";
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'GET', $url );
        
        if ( !empty ( $filter ) )
            $request->set_parameter ( 'q', $filter );
            
            // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        $response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
        
        $tokens = json_decode ( $response, true );
        $err = $this->json_last_error_msg_dep ();
        if ( !empty ( $err ) )
            throw new NimbusecException ( "JSON: an error occured '{$err}' while trying to decode {$response}" );
        else
            return $tokens;
    }

    /**
     * Delete a specific token.
     * The destination path for the request is determined by the ID.
     *
     * No return value.
     *
     * @param int $userID - The user's assigned ID (must be valid)
     */
    function deleteAgentToken ( $tokenID ) {

        $url = $this->DEFAULT_BASE_URL . "/v2/agent/token/" . $tokenID;
        
        // -- Create OAuth request based on OAuth consumer and the specific url --
        $request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'DELETE', $url );
        
        // -- Make signed OAuth request to contact API server --
        $request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
        
        // -- Get the usable url for the request --
        $requestUrl = $request->to_url ();
        
        // -- Run the cUrl request --
        // -- NOTE: This request would basically return nothing, but as the empty HTTP Response body string will be cut off from the header string
        //    through "substr" which for its part fails it returns boolean false --
        $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
    }

    /**
     * This method is used as for a PHP 5 < 5.5.0 environment.<br /> It determines the last JSON error and return a error message <i>Note: The method
     * json_last_error_msg() does the same, but is included <b>only</b> in PHP 5 >= 5.5.0</i>
     *
     * @return string | NULL - Null on success (no error) or the JSON error message on failure
     */
    private function json_last_error_msg_dep ( ) {

        static $errors = array (
                JSON_ERROR_NONE => null,
                JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
                JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
                JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
                JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
                JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
        );
        $error = json_last_error ();
        return array_key_exists ( $error, $errors ) ? $errors[$error] : "Unknown error ({$error})";
    }
}

class NimbusecException extends Exception {
    // pass
}

?>