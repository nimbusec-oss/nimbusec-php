<?php
namespace Nimbusec;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

use Webmozart\PathUtil\Path;
use Composer\CaBundle\CaBundle;

use Nimbusec\OAuth\Consumer as OAuthConsumer;
use Nimbusec\OAuth\Request as OAuthRequest;
use Nimbusec\OAuth\SignatureMethod\HMACSHA1 as OAuthSignatureMethod_HMAC_SHA1;

use Exception;

/**
 * The official Nimbusec API client written in PHP.
 * It uses the GuzzleHttp Library to communicate with the Nimbusec API via SSL and the OAuth standard to get the required authentification.
 *
 * All objects being passed as method parameters must be non-JSON associative arrays as they'll be encoded / decoded and later on send to the server within the method.
 * Otherwise an exception will be thrown.
 *
 * Please note that this API client may not be complete as for the possible operations described in the Nimbusec API documentation. The API client will be expanded when additional features are needed.
 * See the documentation for more details.
 */
class API
{
    // the default url for the API
    const DEFAULT_URL = "https://api.nimbusec.com";

    private $consumer = null;
    private $client = null;

    public function __construct($key, $secret, $url = self::DEFAULT_URL, $options = [])
    {
        $defaults = [
            "base_uri" => $url,
            "verify" => CaBundle::getBundledCaBundlePath(),
            "http_errors" => false,
            "connect_timeout" => 20,
        ];

        // append options to config array
        $config = $defaults + $options;

        $this->client = new Client($config);
        $this->consumer = new OAuthConsumer($key, $secret);
    }

    /**
     * Formats a given GuzzleHttp response and retrieves besides the X-Nimbusec-Error header.
     *
     * @param Response $response The given response.
     * @return string The formates response message.
     */
    private function convertToString(Response $response)
    {
        return trim(sprintf("%s: %s %s",
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            $response->hasHeader("X-Nimbusec-Error") ? implode(", ", $response->getHeader("X-Nimbusec-Error")) : ""));
    }

    /**
     * Concatenates a given path with the client's base uri
     *
     * @param string $path The given path
     * @param boolean $trailing Whether a trailing '/' should be added or not.
     * @return string The concatenated url.
     */
    private function toFullURL($path, $trailing = false)
    {
        $url = Path::join((string) $this->client->getConfig("base_uri"), $path);
        if ($trailing) {
            $url .= "/";
        }
        return $url;
    }

    // ========================================== [ PING ] ==========================================

    public function ping()
    {
        $url = $this->toFullURL("/v3/ping");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $pong = json_decode($response->getBody()->getContents(), true);
        if ($pong === null) {
            throw new Exception(json_last_error_msg());
        }

        return $pong;
    }

    // ========================================= [ BUNDLE ] =========================================

    
    public function getBundle($id)
    {
        $url = $this->toFullURL("/v3/bundles/{$id}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->put($request->to_url());
        // echo $response->getBody();
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }


        $bundles = json_decode($response->getBody()->getContents(), true);
        if ($bundles === null) {
            throw new Exception(json_last_error_msg());
        }

        return $bundles;
    }

    public function listBundles(){
        $url = $this->toFullURL("v3/bundles");
        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        // echo $response->getBody();
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $bundles = json_decode($response->getBody()->getContents(), true);
        if ($bundles === null) {
            throw new Exception(json_last_error_msg());
        }

        return $bundles;
    }


    // ========================================= [ DOMAIN ] =========================================

    /**
     * Issues the API to create the given domain.
     *
     * @param array $domain The given domain.
     * @param boolean $upsert Optional. When set to true, creating an already existing domain will not result in an error.
     *                        Instead, it will update the existing domain with the new fields.
     * @return array The created (or updated) domain.
     */
    public function createDomain(array $domain, $upsert = false)
    {
        $url = $this->toFullURL("/v3/domains");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "POST", $url);
        if ($upsert) {
            $request->set_parameter("upsert", $upsert);
        }

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->post($request->to_url(), ["json" => $domain]);
        // "all 200 status codes are results of successfull REST requests"
        // substr($response->getStatusCode(), 0, 1)!=="2"
        if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 201) {
            throw new Exception($this->convertToString($response));
        }
        
        $domain = json_decode($response->getBody()->getContents(), true);
        if ($domain === null) {
            throw new Exception(json_last_error_msg());
        }

        return $domain;
    }

    public function getDomain($id)
    {
        $url = $this->toFullURL("/v3/domains/{$id}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->put($request->to_url());
        echo $response->getBody();
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }


        $domain = json_decode($response->getBody()->getContents(), true);
        if ($domain === null) {
            throw new Exception(json_last_error_msg());
        }

        return $domain;
    }

    /**
     * Searches for domains that match the given filter criteria.
     *
     * @param string $filter Optional. An FQL based filter.
     * @return array A list of found domains.
     */
    //TODO: delete
    public function findDomains($filter = null)
    {
        $url = $this->toFullURL("/v3/domains");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);
        $request->set_parameter("q", $filter);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $domains = json_decode($response->getBody()->getContents(), true);
        if ($domains === null) {
            throw new Exception(json_last_error_msg());
        }

        return $domains;
    }

    /**
     * Issues the API to update a given domain.
     *
     * @param integer $id The id of the domain which should be updated.
     * @param array $domain The new domain containing all fields which should be updated.
     * @return array The updated domain.
     */
    public function updateDomain($id, array $domain)
    {
        $url = $this->toFullURL("/v3/domains/{$id}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "PUT", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->put($request->to_url(), ["json" => $domain]);
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $domain = json_decode($response->getBody()->getContents(), true);
        if ($domain === null) {
            throw new Exception(json_last_error_msg());
        }

        return $domain;
    }

    /**
     * Issues the API to delete a domain.
     *
     * @param interger $id The id of the domain to be deleted.
     */
    public function deleteDomain($id)
    {
        $url = $this->toFullURL("v3/domains/{$id}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "DELETE", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);
    
        // send request
        $response = $this->client->delete($request->to_url());
        // 204 is the correct response for successfull delete
        if ($response->getStatusCode() !== 204) {
            throw new Exception($this->convertToString($response));
        }
    }

    // ========================================= [ METADATA ] =========================================

    public function getDomainMetadata($domainId){
        // $url = $this->toFullURL("v3/domains/{$domainId}/metadata");
        $url = $this->toFullURL("v3/domains/{$domainId}/metadata");
        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $metadata = json_decode($response->getBody()->getContents(), true);
        if ($metadata === null) {
            throw new Exception(json_last_error_msg());
        }

        return $metadata;
    }

    public function listDomainMetadata(){
        $url = $this->toFullURL("v3/domains/metadata");
        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $metadata = json_decode($response->getBody()->getContents(), true);
        if ($metadata === null) {
            throw new Exception(json_last_error_msg());
        }

        return $metadata;
    }

    // ========================================= [ STATISTICS ] =========================================

    public function listStats(){
        $url = $this->toFullURL("v3/domains/stats");
        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $stats = json_decode($response->getBody()->getContents(), true);
        if ($stats === null) {
            throw new Exception(json_last_error_msg());
        }

        return $stats;
    }

    // ========================================= [ NOTIFICATIONS ] =========================================

    public function listNotifications(){
        $url = $this->toFullURL("v3/notifications");
        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $stats = json_decode($response->getBody()->getContents(), true);
        if ($stats === null) {
            throw new Exception(json_last_error_msg());
        }

        return $stats;
    }

    public function createNotification($notification)
    {
        $url = $this->toFullURL("/v3/notifications");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "POST", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->post($request->to_url(), ["json" => $notification]);
        if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 201) {
            throw new Exception($this->convertToString($response));
        }

        $notification = json_decode($response->getBody()->getContents(), true);
        if ($notification === null) {
            throw new Exception(json_last_error_msg());
        }

        return $notification;
    }

    public function getNotification($id)
    {
        $url = $this->toFullURL("v3/notifications/{$id}");
        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $notification = json_decode($response->getBody()->getContents(), true);
        if ($notification === null) {
            throw new Exception(json_last_error_msg());
        }

        return $notification;
    }

    public function updateNotification($id, array $notification)
    {
        $url = $this->toFullURL("/v3/notifications/{$id}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "PUT", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->put($request->to_url(), ["json" => $notification]);
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $notification = json_decode($response->getBody()->getContents(), true);
        if ($notification === null) {
            throw new Exception(json_last_error_msg());
        }

        return $notification;
    }

    public function deleteNotification($id)
    {
        $url = $this->toFullURL("v3/notifications/{$id}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "DELETE", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);
    
        // send request
        $response = $this->client->delete($request->to_url());
        // 204 is the correct response for successfull delete
        echo $response->getBody();
        if ($response->getStatusCode() !== 204) {
            throw new Exception($this->convertToString($response));
        }
    }

    public function getDomainNotifications($domainId){
        $url = $this->toFullURL("v3/domains/{$domainId}/notifications");
        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $notifications = json_decode($response->getBody()->getContents(), true);
        if ($notifications === null) {
            throw new Exception(json_last_error_msg());
        }

        return $notifications;
    }

    public function getUserNotifications($userId){
        $url = $this->toFullURL("v3/users/{$userId}/notifications");
        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $notifications = json_decode($response->getBody()->getContents(), true);
        if ($notifications === null) {
            throw new Exception(json_last_error_msg());
        }

        return $notifications;
    }

    // ========================================= [ ISSUES ] =========================================

    public function getIssue($id)
    {
        $url = $this->toFullURL("v3/issues/{$id}");
        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $issue = json_decode($response->getBody()->getContents(), true);
        if ($issue === null) {
            throw new Exception(json_last_error_msg());
        }

        return $issue;
    }

    public function listIssues(){
        $url = $this->toFullURL("v3/issues");
        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $issues = json_decode($response->getBody()->getContents(), true);
        if ($issues === null) {
            throw new Exception(json_last_error_msg());
        }

        return $issues;
    }

    public function updateIssue($id, array $issue)
    {
        $url = $this->toFullURL("/v3/issues/{$id}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "PUT", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->put($request->to_url(), ["json" => $issue]);
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $issue = json_decode($response->getBody()->getContents(), true);
        if ($issue === null) {
            throw new Exception(json_last_error_msg());
        }

        return $issue;
    }

    public function getDomainIssues(){
        // $url = $this->toFullURL("v3/domains/{$domainId}/metadata");
        // $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);

        // $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // // send request
        // $response = $this->client->get($request->to_url());
        // if ($response->getStatusCode() !== 200) {
        //     throw new Exception($this->convertToString($response));
        // }

        // $metadata = json_decode($response->getBody()->getContents(), true);
        // if ($metadata === null) {
        //     throw new Exception(json_last_error_msg());
        // }

        // return $metadata;
        //TODO: needs a filter!!!!!!!!!!!!
    }

    public function listIssueHistory(){
        $url = $this->toFullURL("v3/issues-summary/history");
        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $history = json_decode($response->getBody()->getContents(), true);
        if ($history === null) {
            throw new Exception(json_last_error_msg());
        }

        return $history;
    }

    // ========================================= [ OLD NOTIFICATION ] =========================================
    /**
     * Searches for notifications which match the given filter criteria.
     *
     * @param integer $userId The user to search for.
     * @param string $filter Optional. An FQL based filter.
     * @return array A list of found notifications.
     */
    public function findNotifications($userId, $filter = null)
    {
        $url = $this->toFullURL("/v2/user/{$userId}/notification");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);
        $request->set_parameter("q", $filter);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $notifications = json_decode($response->getBody()->getContents(), true);
        if ($notifications === null) {
            throw new Exception(json_last_error_msg());
        }

        return $notifications;
    }

    // ========================================= [ USER DOMAIN SET ] =========================================

    /**
     * Issues the API to create a domain set, assigning a given domain to a given user.
     *
     * @param integer $userId The given user.
     * @param integer $domainId The given domain.
     * @return array A list of domain sets for the user.
     */
    public function createDomainSet($userId, $domainId)
    {
        $url = $this->toFullURL("/v2/user/{$userId}/domains");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "POST", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->post($request->to_url(), ["json" => $domainId]);
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $domainSet = json_decode($response->getBody()->getContents(), true);
        if ($domainSet === null) {
            throw new Exception(json_last_error_msg());
        }

        return $domainSet;
    }

    /**
     * Searches for a domain set by the given user.
     *
     * @param integer $userId The user to search for.
     * @return array A list of domains.
     */
    public function findDomainSet($userId)
    {
        $url = $this->toFullURL("/v2/user/{$userId}/domains");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $domainSet = json_decode($response->getBody()->getContents(), true);
        if ($domainSet === null) {
            throw new Exception(json_last_error_msg());
        }

        return $domainSet;
    }

    /**
     * Issues the API to delete the given domain from the given user's domain set.
     *
     * @param integer $userId The user to search for.
     * @param integer $domainId The domain to delete.
     */
    public function deleteFromDomainSet($userId, $domainId)
    {
        $url = $this->toFullURL("/v2/user/{$userId}/domains/{$domainId}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "DELETE", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->delete($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }
    }

    // ========================================= [ AGENT ] =========================================

    /**
     * Searches for server agents which match the given filter criteria.
     *
     * @param integer $filter Optional. An FQL based filter.
     * @return array A list of found server agents.
     */
    public function findServerAgents($filter = null)
    {
        $url = $this->toFullURL("/v2/agent/download");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);
        $request->set_parameter("q", $filter);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $agents = json_decode($response->getBody()->getContents(), true);
        if ($agents === null) {
            throw new Exception(json_last_error_msg());
        }

        return $agents;
    }

    /**
     * Downloads you a specific server agent.
     *
     * @param string $os The target operating system.
     * @param string $arch The target architecture.
     * @param string $version The target version.
     * @param string $type The file type. Default on "tar.gz".
     * @return The found server agent binary
     */
    public function findSpecificServerAgent($os, $arch, $version, $type = "tar.gz")
    {
        $url = $this->toFullURL("/v2/agent/download/nimbusagent-{$os}-{$arch}-v{$version}.{$type}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        return $response->getBody()->getContents();
    }

    // ========================================= [ AGENT TOKEN ] =========================================

    /**
     * Issues the API to create the given agent token.
     *
     * @param array $token The given agent token.
     * @return array The created server agent token.
     */
    public function createAgentToken($token)
    {
        $url = $this->toFullURL("/v2/agent/token");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "POST", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->post($request->to_url(), ["json" => $token]);
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $token = json_decode($response->getBody()->getContents(), true);
        if ($token === null) {
            throw new Exception(json_last_error_msg());
        }

        return $token;
    }

    /**
     * Searches for agent token which match the given filter criteria.
     *
     * @param string $filter Optional. An FQL based filter.
     * @return array A list of found agent token.
     */
    public function findAgentToken($filter = null)
    {
        $url = $this->toFullURL("/v2/agent/token");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);
        $request->set_parameter("q", $filter);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $token = json_decode($response->getBody()->getContents(), true);
        if ($token === null) {
            throw new Exception(json_last_error_msg());
        }

        return $token;
    }

    /**
     * Issues the API to delete an agent token.
     *
     * @param integer $id The agent token to be deleted.
     */
    public function deleteAgentToken($id)
    {
        $url = $this->toFullURL("v2/agent/token/{$id}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "DELETE", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);
    
        // send request
        $response = $this->client->delete($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }
    }
    
    /**
     * Searches for screenshots of a given domain ID.
     *
     * @param string $domainId Required. Domain ID to fetch screenshots for.
     * @return array A list of found screenshots.
     */
    public function findScreenshots($domainId)
    {
        $domainId = intval($domainId);
        $url = $this->toFullURL("/v2/domain/" . $domainId . "/screenshot");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $screenshots = json_decode($response->getBody()->getContents(), true);
        if ($screenshots === null) {
            throw new Exception(json_last_error_msg());
        }

        return $screenshots;
    }

    /**
     * Get a screenshot from the URL received from @findScreenshots
     *
     * @param string $screenshotUrl Required. URL of the screenshot (path)
     * @return binary string of image which can be read by imagecreatefromstrimg - https://www.php.net/manual/en/function.imagejpeg.php
     */
    public function getScreenshotFromUrl($screenshotUrl)
    {
        $url = $this->toFullURL($screenshotUrl);

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $screenshotStr = $response->getBody()->getContents();
        if ($screenshotStr === null) {
            throw new Exception(json_last_error_msg());
        }

        return $screenshotStr;
    }
}
