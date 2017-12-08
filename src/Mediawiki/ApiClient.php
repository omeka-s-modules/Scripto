<?php
namespace Scripto\Mediawiki;

use Scripto\Mediawiki\Exception;
use Zend\Http\Client as HttpClient;
use Zend\Http\Request;
use Zend\Session\Container;

/**
 * MediaWiki API client
 *
 * @todo Pass Omeka S URL to constructor for *returnurl
 */
class ApiClient
{
    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var string
     */
    protected $apiUri;

    /**
     * @var Container
     */
    protected $session;

    /**
     * @param HttpClient $client
     * @param string $apiUri
     */
    public function __construct(HttpClient $httpClient, $apiUri) {
        $this->httpClient = $httpClient;
        $this->apiUri = $apiUri;

        // Retrieve persisted MediaWiki cookies and add them to the HTTP client.
        $this->session = new Container('ScriptoMediawiki');
        if (is_array($this->session->cookies)) {
            foreach ($this->session->cookies as $cookie) {
                $this->httpClient->addCookie($cookie);
            }
        }
    }

    /**
     * Create a MediaWiki account using the default requests.
     *
     * @param string $username Username for authentication
     * @param string $password Password for authentication
     * @param string $retype Retype password
     * @param string $email Email address
     * @param string $realname Real name of the user
     */
    public function createaccount($username, $password, $retype, $email, $realname)
    {
        $query = $this->request([
            'action' => 'query',
            'meta' => 'tokens',
            'type' => 'createaccount'
        ]);
        $createaccount = $this->request([
            'action' => 'createaccount',
            'createreturnurl' => 'http://example.com',
            'createtoken' => $query['query']['tokens']['createaccounttoken'],
            'username' => $username,
            'password' => $password,
            'retype' => $password,
            'email' => $email,
            'realname' => $realname,
        ]);
        if ('FAIL' === $createaccount['createaccount']['status']) {
            throw new Exception\CreateaccountException($createaccount['createaccount']['message']);
        }
    }

    /**
     * Log in to MediaWiki using the default requests.
     *
     * @param string $username Username for authentication
     * @param string $password Password for authentication
     */
    public function clientlogin($username, $password)
    {
        $query = $this->request([
            'action' => 'query',
            'meta' => 'tokens',
            'type' => 'login'
        ]);
        $clientlogin = $this->request([
            'action' => 'clientlogin',
            'loginreturnurl' => 'http://example.com',
            'logintoken' => $query['query']['tokens']['logintoken'],
            'username' => $username,
            'password' => $password,
        ]);
        if ('FAIL' === $clientlogin['clientlogin']['status']) {
            throw new Exception\ClientloginException($clientlogin['clientlogin']['message']);
        }
        // Persist the authentication cookies.
        $this->session->cookies = $this->httpClient->getCookies();
    }

    /**
     * Log out of MediaWiki.
     */
    public function logout()
    {
        $this->request(['action' => 'logout']); // MediaWiki logout
        $this->httpClient->clearCookies(); // HTTP client logout
        $this->session->cookies = null; // Session logout
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
        $request->setUri($this->apiUri);
        $request->setMethod(Request::METHOD_POST);
        $request->getPost()->fromArray($params);

        $response = $this->httpClient->send($request);
        if ($response->isSuccess()) {
            return json_decode($response->getBody(), true);
        }
        throw new \Exception($response->renderStatusLine());
    }
}
