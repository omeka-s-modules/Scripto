<?php
namespace Scripto\Mediawiki;

use Zend\Http\Client as HttpClient;
use Zend\Http\Request;
use Zend\Session\Container;

/**
 * MediaWiki API client
 *
 * @todo Change \Exception to Scripto\Mediawiki\Exception\*
 * @todo Pass Omeka S URL to constructor for loginreturnurl in login()
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
     * Log in to MediaWiki using the default PasswordAuthenticationRequest.
     *
     * @param string $username
     * @param string $password
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
            'loginreturnurl' => 'http://example.com',
            'logintoken' => $query['query']['tokens']['logintoken'],
            'username' => $username,
            'password' => $password,
        ]);
        if (isset($clientlogin['error'])) {
            throw new \Exception($clientlogin['error']['info']);
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
