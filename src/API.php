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

    /**
     * Checks if the connection to the api can be established.
     *
     * @return string "pong".
     */
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

    /**
     * Get a list of all bundles that are active for the current account.
     *
     * @return array an array of bundles.
     */
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
    
     /**
     * Finds a specific bundle using its id
     *
     * @param string id of the bundle
     * @return array a singular bundle.
     */
    public function getBundle($id)
    {
        $url = $this->toFullURL("v3/bundles/{$id}");
        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $bundle = json_decode($response->getBody()->getContents(), true);
        if ($bundle === null) {
            throw new Exception(json_last_error_msg());
        }

        return $bundle;
    }

    // ========================================= [ DOMAIN ] =========================================

    /**
     * Lists all domains.
     *
     * @return array A list of found domains.
     */
    public function listDomains()
    {
        $url = $this->toFullURL("/v3/domains");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);

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
     * Issues the API to create the given domain.
     *
     * @param array $domain The given domain.
     * @return array The created domain.
     */
    public function createDomain(array $domain)
    {
        $url = $this->toFullURL("/v3/domains");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "POST", $url);

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

     /**
     * Finds a certain domain by its id.
     *
     * @param string $id the id of the domain
     * @return array the domain matching our id.
     */
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
     * Issues the API to update a given domain.
     *
     * @param string $id The id of the domain which should be updated.
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
     * @param string $id The id of the domain to be deleted.
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

    /**
     * Get a list of domain metadata for all domains in your account
     *
     * @return array A list of found metadata.
     */
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

    /**
     * Lists the metadata of a domain in the system.
     *
     * @param string $domainId the domain to find.
     * @return array an array of found metadata.
     */
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

    /**
     * Lists the statistics of all domains.
     *
     * @return array an array of found statistics.
     */
    public function listDomainStats(){
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

    /**
     * Lists all notification linked to your account.
     *
     * @return array an array of found notifications.
     */
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

    /**
     * Creates a new notification.
     *
     * @param array $notification the new notification to create.
     * @return array the newly created notification.
     */
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

    /**
     * Finds a specific notification by id.
     *
     * @param string $id the notifications id to find.
     * @return array the corresponding notification.
     */
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


    /**
     * This requests updates a domain object in Nimbusec Website Security Monitor. Only changing the notification levels is supported for an update. 
     * If you want to get notified for a different domain or via a different transport, create a new notification configuration instead.
     *
     * @param string $id the notfication to find.
     * @param array the notification with updated status
     * @return array the updated notification.
     */
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

    /**
     * Deletes a notification
     *
     * @param string $id the id of the notification to delete.
     */
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

    /**
     * Gets all notifications corresponding to a given domain
     *
     * @param string $domainId the target domain.
     * @return array $notifications array of notifications.
     */
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

    /**
     * Gets all notifications corresponding to a given user
     *
     * @param string $userId the id of target user.
     * @return array $notifications array of notifications.
     */
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

    /**
     * Gets all issues.
     *
     * @return array $issues array of issues.
     */
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

    /**
     * Gets a specific issue by id
     *
     * @param string $id the target issue.
     * @return array $issue the corresponding issue.
     */
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

    /**
     * This request updates the issue status and its external ids. Issues can only be marked as "acknowledged" or "ignored", with an acompanying audit log entry. 
     * WARNING: External IDs are overwritten on each request with the given payload.
     *
     * @param string $id the target issue.
     * @param string $issue the changed issue to be updated.
     * @return array $issue the updated issue.
     */
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

    /**
     * Gets all issues for a domain
     *
     * @param string $domainId the target domain.
     * @return array $issues the corresponding issues.
     */
    public function getDomainIssues($domainId){
        $url = $this->toFullURL("v3/domains/{$domainId}/issues");
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

    /**
     * Returns a issues counts grouped by the specified duration field
     *
     * @return array $history the corresponding history.
     */
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
}
