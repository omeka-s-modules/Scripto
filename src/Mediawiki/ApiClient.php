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
     * Get information about a single page, including revisions.
     *
     * @param string $title The page title
     * @param int $revisionLimit The maximum number of revisions to return
     * @param bool $content Get the revision content?
     * @return array|null Returns null if the page is not created
     */
    public function getPage($title, $revisionLimit = 50, $content = false)
    {
        if (!is_string($title)) {
            throw new Exception\InvalidArgumentException('Page title must be a string');
        }
        if (strstr($title, '|')) {
            throw new Exception\InvalidArgumentException('Can only get one page at a time');
        }
        $rvprop = ['ids', 'flags', 'timestamp', 'comment', 'user'];
        if ($content) {
            $rvprop[] = 'content';
        }
        $query = $this->request([
            'action' => 'query',
            'prop' => 'revisions',
            'titles' => $title,
            'rvlimit' => $revisionLimit,
            'rvprop' => implode('|', $rvprop),
        ]);
        if (0 >= key($query['query']['pages'])) {
            // A zero or negative index indicates an uncreated page.
            return null;
        }
        return reset($query['query']['pages']);
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
    public function isLoggedIn()
    {
        return isset($this->userInfo['query']['userinfo'])
            ? (bool) $this->userInfo['query']['userinfo']['id']
            : false;
    }

    /**
     * Make a HTTP request.
     *
     * @param array $params
     */
    public function request(array $params = [])
    {
        $params['format'] = 'json';

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
