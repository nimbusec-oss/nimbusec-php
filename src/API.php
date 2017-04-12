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

class API
{
    const DEFAULT_URL = "https://api.nimbusec.com";

    private $consumer = null;
    private $client = null;

    public function __construct($key, $secret, $url = self::DEFAULT_URL)
    {
        $this->client = new Client([
            'base_uri' => $url,
            'verify' => CaBundle::getBundledCaBundlePath(),
            'http_errors' => false,
            'connect_timeout' => 20,
        ]);

        $this->consumer = new OAuthConsumer($key, $secret);
    }

    private function convertToString(Response $response)
    {
        return trim(sprintf("%s: %s %s",
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            $response->hasHeader('X-Nimbusec-Error') ? implode(", ", $response->getHeader('X-Nimbusec-Error')) : ''));
    }

    private function toFullURL($path)
    {
        return Path::join((string) $this->client->getConfig('base_uri'), $path);
    }

    // ========================================= [ DOMAIN ] =========================================

    public function createDomain(array $domain, $upsert = false)
    {
        $url = $this->toFullURL("/v2/domain");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'POST', $url);
        if ($upsert) {
            $request->set_parameter('upsert', $upsert);
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

    public function findDomains($filter = null)
    {
        $url = $this->toFullURL("/v2/domain");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'GET', $url);
        $request->set_parameter('q', $filter);

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

    public function updateDomain($id, array $domain)
    {
        $url = $this->toFullURL("/v2/domain/{$id}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'PUT', $url);
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

    public function deleteDomain($id)
    {
        $url = $this->toFullURL("v2/domain/{$id}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'DELETE', $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);
    
        // send request
        $response = $this->client->delete($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }
    }

    // ========================================= [ RESULT ] =========================================

    public function findResults($domainId, $filter = null)
    {
        $url = $this->toFullURL("v2/domain/{$domainId}/result");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'GET', $url);
        $request->set_parameter('q', $filter);

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

    public function findSpecificResult($domainId, $resultId)
    {
        $url = $this->toFullURL("v2/domain/{$domainId}/result/{$resultId}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'GET', $url);
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

    // ========================================= [ APPLICATION ] =========================================

    public function findApplications($domainId)
    {
        $url = $this->toFullURL("v2/domain/{$domainId}/applications");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'GET', $url);
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

    public function findBundles($filter = null)
    {
        $url = $this->toFullURL("/v2/bundle");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'GET', $url);
        $request->set_parameter('q', $filter);

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

    // ========================================= [ AGENT ] =========================================

    public function findServerAgents($filter = null)
    {
        $url = $this->toFullURL("/v2/agent/download");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'GET', $url);
        $request->set_parameter('q', $filter);

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

    public function findSpecificServerAgent($os, $arch, $version, $type = "tar.gz")
    {
        $url = $this->toFullURL("/v2/agent/download/nimbusagent-{$os}-{$arch}-v{$version}.{$type}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'GET', $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        return $response->getBody()->getContents();
    }

    // ========================================= [ AGENT TOKEN ] =========================================

    public function createAgentToken($token)
    {
        $url = $this->toFullURL("/v2/agent/token");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'POST', $url);
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

    public function findAgentToken($filter = null)
    {
        $url = $this->toFullURL("/v2/agent/token");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'GET', $url);
        $request->set_parameter('q', $filter);

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

    public function deleteAgentToken($id)
    {
        $url = $this->toFullURL("v2/agent/token/{$id}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'DELETE', $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);
    
        // send request
        $response = $this->client->delete($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }
    }

    // ========================================= [ CUSTOMER ] =========================================

    public function createUser(array $user, $upsert = false)
    {
        $url = $this->toFullURL("/v2/user");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'POST', $url);
        if ($upsert) {
            $request->set_parameter('upsert', $upsert);
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

    public function findUsers($filter = null)
    {
        $url = $this->toFullURL("/v2/user");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'GET', $url);
        $request->set_parameter('q', $filter);

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

    public function updateUser($id, array $user)
    {
        $url = $this->toFullURL("/v2/user/{$id}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'PUT', $url);
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

    public function deleteUser($id)
    {
        $url = $this->toFullURL("v2/user/{$id}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'DELETE', $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);
    
        // send request
        $response = $this->client->delete($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }
    }

    // ========================================= [ USER CONFIGURATION ] =========================================

    public function setUserConfiguration($id, $key, $value)
    {
        $url = $this->toFullURL("/v2/user/{$id}/config/{$key}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'PUT', $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->put($request->to_url(), ["json" => $value]);
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        return $response->getBody()->getContents();
    }

    public function findUserConfigurations($id)
    {
        $url = $this->toFullURL("/v2/user/{$id}/config");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'GET', $url);
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

    public function findSpecificUserConfiguration($id, $key)
    {
        $url = $this->toFullURL("/v2/user/{$id}/config/{$key}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'GET', $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->get($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }

        $conf = json_decode($response->getBody()->getContents(), true);
        if ($conf === null) {
            throw new Exception(json_last_error_msg());
        }

        return $conf;
    }

    public function deleteUserConfiguration($id, $key)
    {
        $url = $this->toFullURL("/v2/user/{$id}/config/{$key}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'DELETE', $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->delete($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }
    }

    // ========================================= [ NOTIFICATION ] =========================================

    public function createNotification($notification, $userId)
    {
        $url = $this->toFullURL("/v2/user/{$userId}/$notification");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'POST', $url);
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

    public function findNotifications($userId, $filter = null)
    {
        $url = $this->toFullURL("/v2/user/{$userId}/notification");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'GET', $url);
        $request->set_parameter('q', $filter);

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

    public function createDomainSet($userId, $domainId)
    {
        $url = $this->toFullURL("/v2/user/{$userId}/domains");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'POST', $url);
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

    public function findDomainSet($userId)
    {
        $url = $this->toFullURL("/v2/user/{$userId}/domains");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'GET', $url);
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

    public function deleteFromDomainSet($userId, $domainId)
    {
        $url = $this->toFullURL("/v2/user/{$userId}/domains/{$domainId}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'DELETE', $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);

        // send request
        $response = $this->client->delete($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }
    }
}
