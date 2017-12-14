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
     * Is this page created?
     *
     * @param string $title
     * @return bool
     */
    public function pageIsCreated($title)
    {
        $pageInfo = $this->getPageInfo($title);
        return !isset($pageInfo['missing']);
    }

    /**
     * Can the user perform this action on this page?
     *
     * Find the available actions in self::getPageInfo() under intestactions.
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
     * Get information about pages.
     *
     * @link https://www.mediawiki.org/wiki/API:Info
     * @link https://www.mediawiki.org/wiki/Manual:User_rights#List_of_permissions
     * @param array $titles Page titles
     * @return array
     */
    public function getPagesInfo(array $titles)
    {
        if (count($titles) !== count(array_unique($titles))) {
            throw new Exception\InvalidArgumentException('Titles must be unique');
        }
        foreach ($titles as $title) {
            if (!is_string($title)) {
                throw new Exception\InvalidArgumentException('A title must be a string');
            }
            if (strstr($title, '|')) {
                throw new Exception\InvalidArgumentException('A title must not contain a vertical bar');
            }
        }
        $pagesInfo = [];
        // The API limits titles to 50 per query.
        foreach (array_chunk($titles, 50) as $titleChunk) {
            $query = $this->request([
                'action' => 'query',
                'prop' => 'info',
                'titles' => implode('|', $titleChunk),
                'inprop' => 'protection|url',
                'intestactions' => 'read|edit|createpage|createtalk|protect|rollback',
            ]);
            if (isset($query['error'])) {
                throw new Exception\QueryException($query['error']['info']);
            }

            // The ordering of the response does not necessarily correspond to
            // the ordering of the input. Here we match the original ordering.
            $normalized = [];
            if (isset($query['query']['normalized']) ) {
                foreach ($query['query']['normalized'] as $value) {
                    $normalized[$value['from']] = $value['to'];
                }
            }
            foreach ($titleChunk as $title) {
                $title = (string) $title;
                $normalizedTitle = isset($normalized[$title]) ? $normalized[$title] : $title;
                foreach ($query['query']['pages'] as  $pageInfo) {
                    if ($pageInfo['title'] === $normalizedTitle) {
                        $pagesInfo[] = $pageInfo;
                        continue;
                    }
                }
            }
        }
        return $pagesInfo;
    }

    /**
     * Edit or create a page.
     *
     * @link https://www.mediawiki.org/wiki/API:Edit
     * @param string $title
     * @param string $text
     * @return array The successful edit result
     */
    public function editPage($title, $text)
    {
        if (!is_string($title)) {
            throw new Exception\InvalidArgumentException('Page title must be a string');
        }
        if (!is_string($text)) {
            throw new Exception\InvalidArgumentException('Page text must be a string');
        }
        $query = $this->request([
            'action' => 'query',
            'meta' => 'tokens',
            'type' => 'csrf'
        ]);
        $edit = $this->request([
            'action' => 'edit',
            'title' => $title,
            'text' => $text,
            'token' => $query['query']['tokens']['csrftoken'],
        ]);
        if (isset($edit['error'])) {
            throw new Exception\EditException($edit['error']['info']);
        }
        return $edit['edit'];
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
     * @return array The successful create account result
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
        return $createaccount['createaccount'];
    }

    /**
     * Log in to MediaWiki using the default requests.
     *
     * @link https://www.mediawiki.org/wiki/API:Login
     * @param string $username Username for authentication
     * @param string $password Password for authentication
     * @return array The successful login result
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
        return $clientlogin['clientlogin'];
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
