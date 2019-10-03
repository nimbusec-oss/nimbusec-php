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
        $url = $this->toFullURL("/v2/domain");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "POST", $url);
        if ($upsert) {
            $request->set_parameter("upsert", $upsert);
        }

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->post($request->to_url(), ["json" => $domain]);
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
    public function findDomains($filter = null)
    {
        $url = $this->toFullURL("/v2/domain");

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
        $url = $this->toFullURL("/v2/domain/{$id}");

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
        $url = $this->toFullURL("v2/domain/{$id}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "DELETE", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);
    
        // send request
        $response = $this->client->delete($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }
    }

    // ========================================= [ INFECTED ] =========================================

    /**
     * Searches for domains that have results / which are infected.
     *
     * @param string $filter Optional. An FQL based filter. Note: this filter applies to results,
     *                       not to the actual domains!
     *
     * @return array A list of found infected domains.
     */
    public function findInfected($filter = null)
    {
        $url = $this->toFullURL("/v2/infected");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);
        $request->set_parameter("q", $filter);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $infected = json_decode($response->getBody()->getContents(), true);
        if ($infected === null) {
            throw new Exception(json_last_error_msg());
        }

        return $infected;
    }

    // ========================================= [ RESULT ] =========================================

    /**
     * Searches for results of a domain which match the given filter criteria.
     *
     * @param integer $domainId The domain where the results should be searches for.
     * @param string $filter Optional. An FQL based filter.
     * @return array A list of found results.
     */
    public function findResults($domainId, $filter = null)
    {
        $url = $this->toFullURL("v2/domain/{$domainId}/result");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);
        $request->set_parameter("q", $filter);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $results = json_decode($response->getBody()->getContents(), true);
        if ($results === null) {
            throw new Exception(json_last_error_msg());
        }

        return $results;
    }

    /**
     * Gets a result of a domain by its id.
     *
     * @param integer $domainId The domain to search for.
     * @param integer $resultId The result to search for.
     * @return array The found result object.
     */
    public function findSpecificResult($domainId, $resultId)
    {
        $url = $this->toFullURL("v2/domain/{$domainId}/result/{$resultId}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $result = json_decode($response->getBody()->getContents(), true);
        if ($result === null) {
            throw new Exception(json_last_error_msg());
        }

        return $result;
    }

    /**
     * Issues the API to update a given result.
     *
     * @param integer $domainId The id of the domain.
     * @param integer $resultId The id of the result which should be updated.
     * @param array $result The new result containing all fields which should be updated.
     * @return array The updated result.
     */
    public function updateResult($domainId, $resultId, array $result)
    {
        $url = $this->toFullURL("/v2/domain/{$domainId}/result/{$resultId}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "PUT", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->put($request->to_url(), ["json" => $result]);
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $result = json_decode($response->getBody()->getContents(), true);
        if ($result === null) {
            throw new Exception(json_last_error_msg());
        }

        return $result;
    }

    // ========================================= [ APPLICATION ] =========================================

    /**
     * Searches for all applications of a given domain.
     *
     * @param integer $domainId The domain to search for.
     * @return array A list of found applications.
     */
    public function findApplications($domainId)
    {
        $url = $this->toFullURL("v2/domain/{$domainId}/applications");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $applications = json_decode($response->getBody()->getContents(), true);
        if ($applications === null) {
            throw new Exception(json_last_error_msg());
        }

        return $applications;
    }

    // ========================================= [ BUNDLE ] =========================================

    /**
     * Searches for bundles which match the given filter criteria.
     *
     * @param string $filter Optional. An FQL based filter.
     * @return array A list of found bundles.
     */
    public function findBundles($filter = null)
    {
        $url = $this->toFullURL("/v2/bundle");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);
        $request->set_parameter("q", $filter);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $bundles = json_decode($response->getBody()->getContents(), true);
        if ($bundles === null) {
            throw new Exception(json_last_error_msg());
        }

        return $bundles;
    }

    // ========================================= [ USER ] =========================================

    /**
     * Issues the API to create the given user.
     *
     * @param array $user The given user.
     * @param boolean $upsert Optional. When set to true, creating an already existing user will not result in an error.
    *                         Instead, it will update the existing user with the new fields.
     * @return array The created (or updated) user.
     */
    public function createUser(array $user, $upsert = false)
    {
        $url = $this->toFullURL("/v2/user");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "POST", $url);
        if ($upsert) {
            $request->set_parameter("upsert", $upsert);
        }

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->post($request->to_url(), ["json" => $user]);
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $user = json_decode($response->getBody()->getContents(), true);
        if ($user === null) {
            throw new Exception(json_last_error_msg());
        }

        return $user;
    }

    /**
     * Searches for users that match the given filter criteria.
     *
     * @param string $filter Optional. An FQL based filter.
     * @return array A list of found users.
     */
    public function findUsers($filter = null)
    {
        $url = $this->toFullURL("/v2/user");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);
        $request->set_parameter("q", $filter);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $users = json_decode($response->getBody()->getContents(), true);
        if ($users === null) {
            throw new Exception(json_last_error_msg());
        }

        return $users;
    }

    /**
     * Issues the API to update a given user.
     *
     * @param integer $id The id of the user which should be updated.
     * @param array $user The new user containing all fields which should be updated.
     * @return array The updated user.
     */
    public function updateUser($id, array $user)
    {
        $url = $this->toFullURL("/v2/user/{$id}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "PUT", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->put($request->to_url(), ["json" => $user]);
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $user = json_decode($response->getBody()->getContents(), true);
        if ($user === null) {
            throw new Exception(json_last_error_msg());
        }

        return $user;
    }

    /**
     * Issues the API to delete a user.
     *
     * @param interger $id The id of the user to be deleted.
     */
    public function deleteUser($id)
    {
        $url = $this->toFullURL("v2/user/{$id}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "DELETE", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);
    
        // send request
        $response = $this->client->delete($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }
    }

    // ========================================= [ USER CONFIGURATION ] =========================================

    /**
     * Sets the a user configuration for a user.
     *
     * @param integer $id The user to set the conf for.
     * @param string $key The key of the conf.
     * @param string $value The value of the conf.
     * @return string The value of the set configuration.
     */
    public function setUserConfiguration($id, $key, $value)
    {
        $url = $this->toFullURL("/v2/user/{$id}/config/{$key}", true);

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "PUT", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->put($request->to_url(), ["body" => $value]);
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        return $response->getBody()->getContents();
    }

    /**
     * Searches for user configurations of a user.
     *
     * @param integer $id The user to search for.
     * @return array A list of user configurations.
     */
    public function findUserConfigurations($id)
    {
        $url = $this->toFullURL("/v2/user/{$id}/config");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $confs = json_decode($response->getBody()->getContents(), true);
        if ($confs === null) {
            throw new Exception(json_last_error_msg());
        }

        return $confs;
    }

    /**
     * Gets the user configuration by its key.
     *
     * @param integer $id The user to search for.
     * @param string $key The key of the conf.
     * @return string The value of the user configuration.
     */
    public function findSpecificUserConfiguration($id, $key)
    {
        $url = $this->toFullURL("/v2/user/{$id}/config/{$key}", true);

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "GET", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }
        
        return $response->getBody()->getContents();
    }

    /**
     * Issues the API to delete a user configuration.
     *
     * @param integer $id The user to search for.
     * @param string $key The key of the conf.
     */
    public function deleteUserConfiguration($id, $key)
    {
        $url = $this->toFullURL("/v2/user/{$id}/config/{$key}", true);

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "DELETE", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->delete($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }
    }

    // ========================================= [ NOTIFICATION ] =========================================

    /**
     * Issues the API to create the given notification for a user.
     *
     * @param array $notification The given notification.
     * @param integer $userId The user to search for.
     * @return The created notification.
     */
    public function createNotification($notification, $userId)
    {
        $url = $this->toFullURL("/v2/user/{$userId}/notification");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, "POST", $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->post($request->to_url(), ["json" => $notification]);
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
