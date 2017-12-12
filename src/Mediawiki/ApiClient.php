<?php
namespace Scripto\Mediawiki;

use Scripto\Mediawiki\Exception;
use Zend\Http\Client as HttpClient;
use Zend\Http\Request;
use Zend\Session\Container;

/**
 * MediaWiki API client
 */
class ApiClient
{
    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * MediaWiki API endpoint URL
     *
     * @var string
     */
    protected $apiUrl;

    /**
     * Return URL for third-party authentication flows
     *
     * Currently unused but required by accountcreation and clientlogin.
     *
     * @var string
     */
    protected $returnUrl;

    /**
     * @var Container
     */
    protected $session;

    /**
     * Cache of MediaWiki site information
     *
     * @var array
     */
    protected $siteInfo;

    /**
     * Cache of MediaWiki user information
     *
     * @var array
     */
    protected $userInfo;

    /**
     * Construct the client.
     *
     * @param HttpClient $client
     * @param string $apiUrl
     * @param string $returnUrl
     */
    public function __construct(HttpClient $httpClient, $apiUrl, $returnUrl)
    {
        $this->httpClient = $httpClient;
        $this->apiUrl = $apiUrl;
        $this->returnUrl = $returnUrl;

        // Retrieve persisted MediaWiki cookies and add them to the HTTP client.
        $this->session = new Container('ScriptoMediawiki');
        if (is_array($this->session->cookies)) {
            foreach ($this->session->cookies as $cookie) {
                $this->httpClient->addCookie($cookie);
            }
        }

        // Set MediaWiki site and user information.
        $this->siteInfo = $this->getSiteInfo();
        $this->userInfo = $this->getUserInfo();
    }

    /**
     * Get information about a single page.
     *
     * @link https://www.mediawiki.org/wiki/API:Info
     * @param string $title The page title
     * @return array
     */
    public function getPageInfo($title)
    {
        if (!is_string($title)) {
            throw new Exception\InvalidArgumentException('Page title must be a string');
        }
        if (strstr($title, '|')) {
            throw new Exception\InvalidArgumentException('Can only get one page at a time');
        }
        $query = $this->request([
            'action' => 'query',
            'prop' => 'info',
            'titles' => $title,
            'intestactions' => 'read|edit|createpage|createtalk|protect|rollback',
        ]);
        if (isset($query['error'])) {
            throw new Exception\QueryException($query['error']['info']);
        }
        return $query['query']['pages'][0];
    }

    /**
     * Is this page created?
     *
     * @param string $title
     * @return bool
     */
    public function pageCreated($title)
    {
        $pageInfo = $this->getPageInfo($title);
        return !isset($pageInfo['missing']);
    }

    /**
     * Can the user perform this action on this page?
     *
     * Actions include: read, edit, createpage, createtalk, protect, rollback
     *
     * @param string $action
     * @param string $title
     * @return bool
     */
    public function userCan($action, $title)
    {
        $pageInfo = $this->getPageInfo($title);
        return isset($pageInfo['actions'][$action])
            ? (bool) $pageInfo['actions'][$action] : false;
    }

    /**
     * Get information about the MediaWiki site.
     *
     * @link https://www.mediawiki.org/wiki/API:Siteinfo
     * @return array
     */
    public function getSiteInfo()
    {
        return $this->request([
            'action' => 'query',
            'meta' => 'siteinfo',
        ]);
    }

    /**
     * Get information about the current MediaWiki user.
     *
     * @link https://www.mediawiki.org/wiki/API:Userinfo
     * @return array
     */
    public function getUserInfo()
    {
        return $this->request([
            'action' => 'query',
            'meta' => 'userinfo',
        ]);
    }

    /**
     * Create a MediaWiki account using the default requests.
     *
     * @link https://www.mediawiki.org/wiki/API:Account_creation
     * @param string $username Username for authentication
     * @param string $password Password for authentication
     * @param string $retype Retype password
     * @param string $email Email address
     * @param string $realname Real name of the user
     */
    public function createAccount($username, $password, $retype, $email, $realname)
    {
        $query = $this->request([
            'action' => 'query',
            'meta' => 'tokens',
            'type' => 'createaccount'
        ]);
        $createaccount = $this->request([
            'action' => 'createaccount',
            'createreturnurl' => $this->returnUrl,
            'createtoken' => $query['query']['tokens']['createaccounttoken'],
            'username' => $username,
            'password' => $password,
            'retype' => $password,
            'email' => $email,
            'realname' => $realname,
        ]);
        if (isset($createaccount['error'])) {
            throw new Exception\CreateaccountException($createaccount['error']['info']);
        }
        if ('FAIL' === $createaccount['createaccount']['status']) {
            throw new Exception\CreateaccountException($createaccount['createaccount']['message']);
        }
    }

    /**
     * Log in to MediaWiki using the default requests.
     *
     * @link https://www.mediawiki.org/wiki/API:Login
     * @param string $username Username for authentication
     * @param string $password Password for authentication
     */
    public function login($username, $password)
    {
        $query = $this->request([
            'action' => 'query',
            'meta' => 'tokens',
            'type' => 'login'
        ]);
        $clientlogin = $this->request([
            'action' => 'clientlogin',
            'loginreturnurl' => $this->returnUrl,
            'logintoken' => $query['query']['tokens']['logintoken'],
            'username' => $username,
            'password' => $password,
        ]);
        if (isset($clientlogin['error'])) {
            throw new Exception\ClientloginException($clientlogin['error']['info']);
        }
        if ('FAIL' === $clientlogin['clientlogin']['status']) {
            throw new Exception\ClientloginException($clientlogin['clientlogin']['message']);
        }
        // Persist the authentication cookies.
        $this->session->cookies = $this->httpClient->getCookies();

        // Set user information.
        $this->userInfo = $this->getUserInfo();
    }

    /**
     * Log out of MediaWiki.
     *
     * @link https://www.mediawiki.org/wiki/API:Logout
     */
    public function logout()
    {
        $this->request(['action' => 'logout']); // Log out of MediaWiki
        $this->httpClient->clearCookies(); // Clear HTTP client cookies
        $this->session->cookies = null; // Clear session cookies
        $this->userInfo = $this->getUserInfo(); // Reset MediaWiki user information
    }

    /**
     * Is the current user logged in?
     *
     * @return bool
     */
    public function userIsLoggedIn()
    {
        return isset($this->userInfo['query']['userinfo'])
            ? (bool) $this->userInfo['query']['userinfo']['id']
            : false;
    }

    /**
     * Make a HTTP request
     *
     * Returns JSON response format version 2.
     *
     * @link https://www.mediawiki.org/wiki/API:JSON_version_2
     * @param array $params
     * @return array
     */
    public function request(array $params = [])
    {
        $params['format'] = 'json';
        $params['formatversion'] = '2';

        $request = new Request;
        $request->setUri($this->apiUrl);
        $request->setMethod(Request::METHOD_POST);
        $request->getPost()->fromArray($params);

        $response = $this->httpClient->send($request);
        if ($response->isSuccess()) {
            return json_decode($response->getBody(), true);
        }
        throw new Exception\RequestException($response->renderStatusLine());
    }
}
