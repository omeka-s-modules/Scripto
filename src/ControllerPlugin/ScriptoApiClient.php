<?php
namespace Scripto\ControllerPlugin;

use Scripto\Mediawiki\ApiClient;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Controller plugin used to access Scripto's MediaWiki API client.
 */
class ScriptoApiClient extends AbstractPlugin
{
    /**
     * @var ApiClient
     */
    protected $apiClient;

    /**
     * @param ApiClient $apiClient
     */
    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Return Scripto's MediaWiki API client.
     *
     * @return ApiClient
     */
    public function __invoke()
    {
        return $this->apiClient;
    }
}
