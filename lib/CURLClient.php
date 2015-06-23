<?php

class CURLClient {
    
    // -- The actual cURL instance -- 
    public $curl;

    function __construct ( ) {
        
        // -- Init curl --
        $this->curl = curl_init ();
        
        // -- Return the transfer as string instead of putting on stdout --
        curl_setopt ( $this->curl, CURLOPT_RETURNTRANSFER, true );
        // -- Tells PHP to give a proper response message on failure (>= 4xx) --
        curl_setopt ( $this->curl, CURLOPT_FAILONERROR, false );
        
        // -- Curl doesn't have built-in root certificates (like most modern browser do).
        //    You need to explicitly point it to a cacert.pem file or a ca bundle
        //    Without this, curl cannot verify the certificate sent back via ssl. This same root
        //    certificate file can be used every time you use SSL in curl
        //    The ca-bundle was automatically converted CA Certs from mozilla.org --
        curl_setopt ( $this->curl, CURLOPT_CAINFO, __DIR__ . "/rootCA/ca-bundle.crt" );
        curl_setopt ( $this->curl, CURLOPT_SSL_VERIFYPEER, true );
        curl_setopt ( $this->curl, CURLOPT_SSL_VERIFYHOST, 2 );
        
        // -- Include HTTP header --
        curl_setopt ( $this->curl, CURLOPT_HEADER, true );
        // -- Max running time for a curl request (in sec.) --
        curl_setopt ( $this->curl, CURLOPT_CONNECTTIMEOUT, 20 );
        // -- Force curl explicitly to connect afresh --
        curl_setopt ( $this->curl, CURLOPT_FRESH_CONNECT, true );
    }

    function __destruct ( ) {

        curl_close ( $this->curl );
    }

    /**
     * Makes an HTTP request to the specified URL; As the SSL option are set upon instantiating the request will be sent through SSL.
     * After receiving the HTTP response, the method verifies the HTTP code. Depending on the status code it either returns the body on success (200)
     * or filters the header by the X-Nimbusec-Error field and return it along with the status on failure. 
     * 
     *
     * @param string $http_method - The HTTP method (GET, POST, PUT, DELETE)
     * @param string $url - Full URL of the resource to be accessed
     * @param string $auth_header - (optional) Authorization header
     * @param string $postData - (optional) POST/PUT request body
     * @return mixed - Response body from the server
     */
    function send_request ( $http_method, $url, $auth_header = null, $postData = null ) {
        
        // -- Reset certain options --
        curl_setopt ( $this->curl, CURLOPT_URL, $url );
        curl_setopt ( $this->curl, CURLOPT_POST, false );
        curl_setopt ( $this->curl, CURLOPT_CUSTOMREQUEST, null );
        
        switch ( $http_method ) {
            case 'GET':
                if ( $auth_header ) {
                    curl_setopt ( $this->curl, CURLOPT_HTTPHEADER, array (
                            $auth_header
                    ) );
                }
                break;
            case 'POST':
                curl_setopt ( $this->curl, CURLOPT_HTTPHEADER, array (
                        'Content-Type: application/json',
                        $auth_header
                ) );
                curl_setopt ( $this->curl, CURLOPT_POST, true );
                curl_setopt ( $this->curl, CURLOPT_POSTFIELDS, $postData );
                break;
            case 'PUT':
                curl_setopt ( $this->curl, CURLOPT_HTTPHEADER, array (
                        'Content-Type: application/json',
                        $auth_header
                ) );
                curl_setopt ( $this->curl, CURLOPT_CUSTOMREQUEST, $http_method );
                curl_setopt ( $this->curl, CURLOPT_POSTFIELDS, $postData );
                break;
            case 'DELETE':
                curl_setopt ( $this->curl, CURLOPT_HTTPHEADER, array (
                        $auth_header
                ) );
                curl_setopt ( $this->curl, CURLOPT_CUSTOMREQUEST, $http_method );
                break;
        }
        
        $response = curl_exec ( $this->curl );
        
        // -- Retrieve status and header size --
        $httpStatus = curl_getinfo ( $this->curl, CURLINFO_HTTP_CODE );
        $header_size = curl_getinfo ( $this->curl, CURLINFO_HEADER_SIZE );
        
        // -- Retrieve header --
        $header = substr ( $response, 0, $header_size );
        // -- Retrieve body --
        $body = substr ( $response, $header_size );
        
        // -- Return body on success --
        if ( $httpStatus == "200" ) {
            return $body;
        }
        
        // -- Split fields --
        $httpFields = explode ( "\n", $header );
        
        // -- Get status --
        $response = $httpFields[0];
        
        // -- Search for our custom field --
        foreach ( $httpFields as $field ) {
            if ( (strpos ( $field, "X-Nimbusec-Error" )) !== false )
                $response .= $field;
        }
        throw new CUrlException ( $response );
    }
}

class CUrlException extends Exception {
    // pass
}
