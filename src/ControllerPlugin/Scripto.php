<?php
namespace Scripto\ControllerPlugin;

use Omeka\Api\Exception\NotFoundException;
use Scripto\Mediawiki\ApiClient;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Controller plugin used for Scripto-specific functionality.
 */
class Scripto extends AbstractPlugin
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
    public function apiClient()
    {
        return $this->apiClient;
    }

    /**
     * Get a Scripto representation.
     *
     * Provides a single method to get a Scripto project, item, or media
     * representation. This is needed primarily becuase Scripto routes via Omeka
     * item and media IDs, not their corresponding Scripto item and media IDs.
     *
     * @return ScriptoProjectRepresentation|ScriptoItemRepresentation|ScriptoMediaRepresentation
     */
    public function getRepresentation($projectId, $itemId = null, $mediaId = null)
    {
        $controller = $this->getController();

        if (!$itemId && $mediaId) {
            // An item ID must accompany a media ID.
            return false;
        }

        try {
            $project = $controller->api()->read('scripto_projects', $projectId)->getContent();
        } catch (NotFoundException $e) {
            return false;
        }

        if (!$itemId && !$mediaId) {
            return $project;
        }
        
        $sItem = $controller->api()->searchOne('scripto_items', [
            'scripto_project_id' => $project->id(),
            'item_id' => $itemId,
        ])->getContent();

        if (!$sItem) {
            // The Scripto item does not exist.
            return false;
        }

        if (!$mediaId) {
            return $sItem;
        }

        $sMedia = $controller->api()->searchOne('scripto_media', [
            'scripto_item_id' => $sItem->id(),
            'media_id' => $mediaId,
        ])->getContent();

        if (!$sMedia) {
            // The Scripto media does not exist.
            return false;
        }

        return $sMedia;
    }

    /**
     * Prepare a MediaWiki list for rendering.
     *
     * @param array $list
     * @return array
     */
    public function prepareMediawikiList(array $list)
    {
        foreach ($list as $key => $row) {
            if (preg_match('/^\d+:\d+:\d+$/', $row['title'])) {
                list($projectId, $itemId, $mediaId) = explode(':', $row['title']);
                $sMedia = $this->getRepresentation($projectId, $itemId, $mediaId);
                if ($sMedia) {
                    $list[$key]['scripto_media'] = $sMedia;
                }
            }
        }
        return $list;
    }

    /**
     * Cache MediaWiki pages.
     *
     * Leverages bulk caching in the API client so subsequent queries of
     * individual pages don't make API requests.
     *
     * @param array An array of Scripto media representations
     */
    public function cacheMediawikiPages(array $sMedia)
    {
        $titles = [];
        foreach ($sMedia as $sm) {
            $titles[] = $sm->pageTitle();
        }
        $this->apiClient()->queryPages($titles);
    }
}
