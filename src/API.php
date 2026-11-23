<?php

namespace Nimbusec;

use kamermans\OAuth2\GrantType\ClientCredentials;
use kamermans\OAuth2\OAuth2Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

use Webmozart\PathUtil\Path;
use Composer\CaBundle\CaBundle;

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
	const OAUTH2_URL = "https://auth.nimbusec.com/oauth2/token";

	private $client = null;
	private $baseUrl = null;

	public function __construct($key, $secret, $url = self::DEFAULT_URL, $options = [])
	{
		$reauth_client = new Client([
			"base_uri" => self::OAUTH2_URL,
		]);
		// create oauth2 capable config
		$reauth_config = [
			"client_id" => $key,
			"client_secret" => $secret,
			"scope" => "onehorn:admin",
		];
		$grant_type = new ClientCredentials($reauth_client, $reauth_config);
		$oauth = new OAuth2Middleware($grant_type);
		
		$stack = HandlerStack::create();
		$stack->push($oauth);

		// create final client
		$defaults = [
			"base_uri" => $url,
			"verify" => CaBundle::getBundledCaBundlePath(),
			"http_errors" => false,
			"connect_timeout" => 20,
			"handler" => $stack,
		];
		// user supplied options take precedence over the defaults
		$config = $options + $defaults;
		$this->client = new Client($config);
		$this->baseUrl = $config["base_uri"];
	}

	/**
	 * Formats a given GuzzleHttp response and retrieves besides the X-Nimbusec-Error header.
	 *
	 * @param Response $response The given response.
	 * @return string The formates response message.
	 */
	private function convertToString(Response $response)
	{
		return trim(sprintf(
			"%s: %s %s",
			$response->getStatusCode(),
			$response->getReasonPhrase(),
			$response->hasHeader("X-Nimbusec-Error") ? implode(", ", $response->getHeader("X-Nimbusec-Error")) : ""
		));
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
		$url = Path::join((string) $this->baseUrl, $path);
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

		// send request
		$response = $this->client->request("GET", $url);
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
	public function listBundles()
	{
		$url = $this->toFullURL("/v3/bundles");

		// send request
		$response = $this->client->request("GET", $url);
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
		$url = $this->toFullURL("/v3/bundles/{$id}");

		// send request
		$response = $this->client->request("GET", $url);
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

		// send request
		$response = $this->client->request("GET", $url);
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

		// send request
		$response = $this->client->request("POST", $url, ["json" => $domain]);
		// "all 200 status codes are results of successful REST requests"
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

		// send request
		$response = $this->client->request("GET", $url);
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

		// send request
		$response = $this->client->request("PUT", $url, ["json" => $domain]);
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
		$url = $this->toFullURL("/v3/domains/{$id}");

		// send request
		$response = $this->client->request("DELETE", $url);
		// 204 is the correct response for successful delete
		if ($response->getStatusCode() !== 204) {
			throw new Exception($this->convertToString($response));
		}
	}

	/**
	 * Patches a specified domain to be disabled.
	 *
	 * @param string $id The id of the domain to be disabled.
	 */
	public function disableDomain($id)
	{
		$url = $this->toFullURL("/v3/domains/{$id}/disable");

		// send request
		$response = $this->client->request("PATCH", $url);
		if ($response->getStatusCode() !== 200) {
			throw new Exception($this->convertToString($response));
		}
	}


	// ========================================= [ METADATA ] =========================================

	/**
	 * Get a list of domain metadata for all domains in your account
	 *
	 * @return array A list of found metadata.
	 */
	public function listDomainMetadata()
	{
		$url = $this->toFullURL("/v3/domains/metadata");

		// send request
		$response = $this->client->request("GET", $url);
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
	public function getDomainMetadata($domainId)
	{
		$url = $this->toFullURL("/v3/domains/{$domainId}/metadata");

		// send request
		$response = $this->client->request("GET", $url);
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
	public function listDomainStats()
	{
		$url = $this->toFullURL("/v3/domains/stats");

		// send request
		$response = $this->client->request("GET", $url);
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
	public function listNotifications()
	{
		$url = $this->toFullURL("/v3/notifications");

		// send request
		$response = $this->client->request("GET", $url);
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
	public function createNotification(array $notification)
	{
		$url = $this->toFullURL("/v3/notifications");

		// send request
		$response = $this->client->request("POST", $url, ["json" => $notification]);
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
		$url = $this->toFullURL("/v3/notifications/{$id}");

		// send request
		$response = $this->client->request("GET", $url);
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

		// send request
		$response = $this->client->request("PUT", $url, ["json" => $notification]);
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
		$url = $this->toFullURL("/v3/notifications/{$id}");

		// send request
		$response = $this->client->request("DELETE", $url);
		// 204 is the correct response for successful delete
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
	public function getDomainNotifications($domainId)
	{
		$url = $this->toFullURL("/v3/domains/{$domainId}/notifications");

		// send request
		$response = $this->client->request("GET", $url);
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
	public function getUserNotifications($userId)
	{
		$url = $this->toFullURL("/v3/users/{$userId}/notifications");

		// send request
		$response = $this->client->request("GET", $url);
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
	public function listIssues()
	{
		$url = $this->toFullURL("/v3/issues");

		// send request
		$response = $this->client->request("GET", $url);
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
		$url = $this->toFullURL("/v3/issues/{$id}");

		// send request
		$response = $this->client->request("GET", $url);
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

		// send request
		$response = $this->client->request("PUT", $url, ["json" => $issue]);
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
	public function getDomainIssues($domainId)
	{
		$url = $this->toFullURL("/v3/domains/{$domainId}/issues");

		// send request
		$response = $this->client->request("GET", $url);
		if ($response->getStatusCode() !== 200) {
			throw new Exception($this->convertToString($response));
		}

		$issues = json_decode($response->getBody()->getContents(), true);
		if ($issues === null) {
			throw new Exception(json_last_error_msg());
		}

		return $issues;
	}

	// ========================================= [ APPLICATIONS ] =========================================

	/**
	 * Returns applications for a certain domain.
	 *
	 * @return array $applications - the corresponding applications.
	 */
	public function listApplicationsByDomain($domainId)
	{
		$url = $this->toFullURL("/v3/domains/{$domainId}/applications");

		// send request
		$response = $this->client->request("GET", $url);
		if ($response->getStatusCode() !== 200) {
			throw new Exception($this->convertToString($response));
		}

		$applications = json_decode($response->getBody()->getContents(), true);
		if ($applications === null) {
			throw new Exception(json_last_error_msg());
		}

		return $applications;
	}

	// ========================================= [ SCREENSHOTS ] =========================================

	/**
	 * Searches for screenshots of a given domain ID.
	 *
	 * @param string $domainId Required. Domain ID to fetch screenshots for.
	 * @return array A list of found screenshots.
	 */
	public function listScreenshotsOfDomain($domainId)
	{
		$domainId = intval($domainId);
		$url = $this->toFullURL("/v3/domains/" . $domainId . "/screenshots");


		// send request
		$response = $this->client->request("GET", $url);
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
	 * Searches for screenshots of a given domain ID, using given name.
	 *
	 * @param string $domainId Domain ID to fetch screenshots for.
	 * @param string $name Name of screenshot to find.
	 * @return string The raw screenshot (JPEG) content.
	 */
	public function findScreenshot($domainId, $name)
	{
		$domainId = intval($domainId);
		$url = $this->toFullURL("/v3/domains/" . $domainId . "/screenshots/" . $name . ".jpg");


		// send request
		$response = $this->client->request("GET", $url);
		if ($response->getStatusCode() !== 200) {
			throw new Exception($this->convertToString($response));
		}

		//echo $response->getBody()->getContents();
		$screenshotStr = $response->getBody()->getContents();
		if ($screenshotStr === null) {
			throw new Exception(json_last_error_msg());
		}
		return $screenshotStr;
	}



	// ========================================= [ ISSUE-HISTORY ] =========================================
	/**
	 * Returns a issues counts grouped by the specified duration field
	 *
	 * @return array $history the corresponding history.
	 */
	public function listIssueHistory()
	{
		$url = $this->toFullURL("/v3/issues-summary/history");

		// send request
		$response = $this->client->request("GET", $url);
		if ($response->getStatusCode() !== 200) {
			throw new Exception($this->convertToString($response));
		}

		$history = json_decode($response->getBody()->getContents(), true);
		if ($history === null) {
			throw new Exception(json_last_error_msg());
		}

		return $history;
	}

	// ========================================= [ AGENTS ] =========================================

	/**
	 * Lists available server agents
	 *
	 * @return array A list of found server agents.
	 */
	public function listAgents()
	{
		$url = $this->toFullURL("/v3/agent/download");


		// send request
		$response = $this->client->request("GET", $url);
		if ($response->getStatusCode() !== 200) {
			throw new Exception($this->convertToString($response));
		}

		$agents = json_decode($response->getBody()->getContents(), true);
		if ($agents === null) {
			throw new Exception(json_last_error_msg());
		}

		return $agents;
	}

	public function findSpecificAgent($os, $arch, $version, $type = "tar.gz")
	{
		$url = $this->toFullURL("/v3/agent/download/nimbusagent-{$os}-{$arch}-v{$version}.{$type}");

		// send request
		$response = $this->client->request("GET", $url);
		if ($response->getStatusCode() !== 200) {
			throw new Exception($this->convertToString($response));
		}

		return $response->getBody()->getContents();
	}



	// ========================================= [ AGENT-TOKENS ] =========================================

	/**
	 * Lists all tokens.
	 *
	 * @return array A list of found tokens.
	 */
	public function listTokens()
	{
		$url = $this->toFullURL("/v3/tokens");

		// send request
		$response = $this->client->request("GET", $url);
		if ($response->getStatusCode() !== 200) {
			throw new Exception($this->convertToString($response));
		}
		$tokens = json_decode($response->getBody()->getContents(), true);
		if ($tokens === null) {
			throw new Exception(json_last_error_msg());
		}

		return $tokens;
	}

	/**
	 * Issues the API to create the given token.
	 *
	 * @param array $token The given token.
	 * @return array The created token.
	 */
	public function createToken(array $token)
	{
		$url = $this->toFullURL("/v3/tokens");

		// send request
		$response = $this->client->request("POST", $url, ["json" => $token]);
		// "all 200 status codes are results of successful REST requests"
		// substr($response->getStatusCode(), 0, 1)!=="2"
		if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 201) {
			throw new Exception($this->convertToString($response));
		}

		$token = json_decode($response->getBody()->getContents(), true);
		if ($token === null) {
			throw new Exception(json_last_error_msg());
		}

		return $token;
	}

	/**
	 * Finds a certain token by its id.
	 *
	 * @param string $id the id of the token
	 * @return array the token matching our id.
	 */
	public function getToken($id)
	{
		$url = $this->toFullURL("/v3/tokens/{$id}");

		// send request
		$response = $this->client->request("GET", $url);
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
	 * Issues the API to delete a token.
	 *
	 * @param string $id The id of the token to be deleted.
	 */
	public function deleteToken($id)
	{
		$url = $this->toFullURL("/v3/tokens/{$id}");

		// send request
		$response = $this->client->request("DELETE", $url);
		// 204 is the correct response for successful delete
		if ($response->getStatusCode() !== 204) {
			throw new Exception($this->convertToString($response));
		}
	}

	// ========================================= [ USERS ] =========================================

	/**
	 * Lists all users.
	 *
	 * @return array A list of found users.
	 */
	public function listUsers()
	{
		$url = $this->toFullURL("/v3/users");

		// send request
		$response = $this->client->request("GET", $url);
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
	 * Issues the API to create the given user.
	 *
	 * @param array $user The given user.
	 * @return array The created user.
	 */
	public function createUser(array $user)
	{
		$url = $this->toFullURL("/v3/users");

		// send request
		$response = $this->client->request("POST", $url, ["json" => $user]);
		// "all 200 status codes are results of successful REST requests"
		// substr($response->getStatusCode(), 0, 1)!=="2"
		if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 201) {
			throw new Exception($this->convertToString($response));
		}

		$user = json_decode($response->getBody()->getContents(), true);
		if ($user === null) {
			throw new Exception(json_last_error_msg());
		}

		return $user;
	}

	/**
	 * Finds a certain user by its id.
	 *
	 * @param string $id the id of the user
	 * @return array the user matching our id.
	 */
	public function getUser($id)
	{
		$url = $this->toFullURL("/v3/users/{$id}");

		// send request
		$response = $this->client->request("GET", $url);
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
	 * Issues the API to update a given user.
	 *
	 * @param string $id The id of the user which should be updated.
	 * @param array $user The new user containing all fields which should be updated.
	 * @return array The updated user.
	 */
	public function updateUser($id, array $user)
	{
		$url = $this->toFullURL("/v3/users/{$id}");

		// send request
		$response = $this->client->request("PUT", $url, ["json" => $user]);
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
	 * @param string $id The id of the user to be deleted.
	 */
	public function deleteUser($id)
	{
		$url = $this->toFullURL("/v3/users/{$id}");

		// send request
		$response = $this->client->request("DELETE", $url);
		// 204 is the correct response for successful delete
		if ($response->getStatusCode() !== 204) {
			throw new Exception($this->convertToString($response));
		}
	}

	// ========================================= [ USER-CONFIGURATION ] =========================================

	/**
	 * List user configuration keys.
	 *
	 * @return array A list of found userconfigs.
	 */
	public function listUserConfigs($id)
	{
		$url = $this->toFullURL("/v3/users/{$id}/configs");

		// send request
		$response = $this->client->request("GET", $url);
		if ($response->getStatusCode() !== 200) {
			throw new Exception($this->convertToString($response));
		}

		$config = json_decode($response->getBody()->getContents(), true);
		if ($config === null) {
			throw new Exception(json_last_error_msg());
		}

		return $config;
	}

	/**
	 * Finds a certain user config by its id.
	 *
	 * @param string $userID the id of the user
	 * @param string $configID the id of the config
	 */
	public function getUserConfig($userID, $configID)
	{
		$url = $this->toFullURL("/v3/users/{$userID}/configs/{$configID}");

		// send request
		$response = $this->client->request("GET", $url);
		if ($response->getStatusCode() !== 200) {
			throw new Exception($this->convertToString($response));
		}

		$config = json_decode($response->getBody()->getContents(), true);
		if ($config === null) {
			throw new Exception(json_last_error_msg());
		}

		return $config;
	}

	/**
	 * Issues the API to update a given user config.
	 *
	 * @param string $userID the id of the user
	 * @param string $configID the id of the config
	 * @param array $config The new config containing all fields which should be updated.
	 * @return array The updated config.
	 */
	public function updateUserConfig($userID, $configID, $config)
	{
		$url = $this->toFullURL("/v3/users/{$userID}/configs/{$configID}");

		// send request
		$response = $this->client->request("PUT", $url, ["json" => $config]);
		if ($response->getStatusCode() !== 200) {
			throw new Exception($this->convertToString($response));
		}

		$config = json_decode($response->getBody()->getContents(), true);
		if ($config === null) {
			throw new Exception(json_last_error_msg());
		}

		return $config;
	}

	/**
	 * Issues the API to delete a users config.
	 *
	 * @param string $userID the id of the user
	 * @param string $configID the id of the config
	 */
	public function deleteUserConfig($userID, $configID)
	{
		$url = $this->toFullURL("/v3/users/{$userID}/configs/{$configID}");

		// send request
		$response = $this->client->request("DELETE", $url);
		// 204 is the correct response for successful delete
		if ($response->getStatusCode() !== 204) {
			throw new Exception($this->convertToString($response));
		}
	}

	// ========================================= [ USER-DOMAIN-SET ] =========================================

	/**
	 * List user assigned domains.
	 *
	 * @return array A list of found domains.
	 */
	public function listUserDomainSet($id)
	{
		$url = $this->toFullURL("/v3/users/{$id}/domains");

		// send request
		$response = $this->client->request("GET", $url);
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
	 * Issues the API to update a given user domain set.
	 *
	 * @param string $userID the id of the user
	 * @param array $domains The new list of domain IDs
	 * @return array The updated domain set.
	 */
	public function updateUserDomainSet($userID, array $domainSet)
	{
		$url = $this->toFullURL("/v3/users/{$userID}/domains");

		// send request
		$response = $this->client->request("PUT", $url, ["json" => $domainSet]);
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
	 * Issues the API to add a domain to given users domain set.
	 *
	 * @param string $userID the id of the user
	 * @param array $domainID The domain to add to said users domain set
	 * @return array The updated domain set.
	 */
	public function addDomainToUserDomainSet($userID, $domainID)
	{
		$url = $this->toFullURL("/v3/users/{$userID}/domains/{$domainID}");

		// send request
		$response = $this->client->request("PUT", $url);
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
	 * Issues the API to remove a domain from given users domain set.
	 *
	 * @param string $userID the id of the user
	 * @param string $domainID the id of the domain
	 */
	public function removeDomainFromUserDomainSet($userID, $domainID)
	{
		$url = $this->toFullURL("/v3/users/{$userID}/domains/{$domainID}");

		// send request
		$response = $this->client->request("DELETE", $url);
		// 204 is the correct response for successful delete
		if ($response->getStatusCode() !== 204) {
			throw new Exception($this->convertToString($response));
		}
	}

	// ========================================= [ USER-FAVORITE ] =========================================

	/**
	 * List users favorite domains.
	 *
	 * @return array A list of found domains.
	 */
	public function listUserFavorites($id)
	{
		$url = $this->toFullURL("/v3/users/{$id}/favorites");

		// send request
		$response = $this->client->request("GET", $url);
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
	 * Issues the API to update a given user favorite domains.
	 *
	 * @param string $userID the id of the user
	 * @param array $domains The new list of domain IDs
	 * @return array The updated domain set.
	 */
	public function updateUserFavorites($userID, $favorites)
	{
		$url = $this->toFullURL("/v3/users/{$userID}/favorites");

		// send request
		$response = $this->client->request("PUT", $url, ["json" => $favorites]);
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
	 * Issues the API to add a domain to given users favorites.
	 *
	 * @param string $userID the id of the user
	 * @param string $domainID The domain to add to said users favorites
	 * @return array The updated list of favorite domains.
	 */
	public function addDomainToUserFavorites($userID, $domainID)
	{
		$url = $this->toFullURL("/v3/users/{$userID}/favorites/{$domainID}");

		// send request
		$response = $this->client->request("PUT", $url);
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
	 * Issues the API to remove a domain from given users favorites.
	 *
	 * @param string $userID the id of the user
	 * @param string $domainID the id of the domain
	 */
	public function removeDomainFromUserFavorites($userID, $domainID)
	{
		$url = $this->toFullURL("/v3/users/{$userID}/favorites/{$domainID}");

		// send request
		$response = $this->client->request("DELETE", $url);
		// 204 is the correct response for successful delete
		if ($response->getStatusCode() !== 204) {
			throw new Exception($this->convertToString($response));
		}
	}
}
