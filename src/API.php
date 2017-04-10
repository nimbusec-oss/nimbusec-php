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

    public function createDomain(array $domain)
    {
        $url = $this->toFullUrl("/v2/domain");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'POST', $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, null);

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
        $url = $this->toFullUrl("/v2/domain");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'GET', $url);
        $request->set_parameter('q', $filter);

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, null);

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
        $url = $this->toFullUrl("/v2/domain/{$id}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'PUT', $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, null);

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
        $url = $this->toFullUrl("v2/domain/{$id}");

        $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'DELETE', $url);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, null);
    
        // send request
        $response = $this->client->delete($request->to_url());
        if ($response->getStatusCode() !== 200) {
            throw new Exception($this->convertToString($response));
        }
    }

    // ========================================= [ CUSTOMER ] =========================================
}
