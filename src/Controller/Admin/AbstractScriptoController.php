<?php
namespace Scripto\Controller\Admin;

use Zend\Mvc\Controller\AbstractActionController;

class AbstractScriptoController extends AbstractActionController
{
    /**
     * Get a Scripto representation.
     *
     * Provides a single method to get a Scripto project, item, or media
     * representation. This is needed primarily becuase Scripto routes via Omeka
     * item and media IDs, not their corresponding Scripto item and media IDs.
     *
     * @return ScriptoProjectRepresentation|ScriptoItemRepresentation|ScriptoMediaRepresentation
     */
    protected function getScriptoRepresentation($projectId, $itemId = null, $mediaId = null)
    {
        if (!$itemId && $mediaId) {
            // An item ID must accompany a media ID.
            return false;
        }

        $project = $this->api()->read('scripto_projects', $projectId)->getContent();

        if (!$itemId && !$mediaId) {
            return $project;
        }
        
        $sItem = $this->api()->searchOne('scripto_items', [
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

        $sMedia = $this->api()->searchOne('scripto_media', [
            'scripto_item_id' => $sItem->id(),
            'media_id' => $mediaId,
        ])->getContent();

        if (!$sMedia) {
            // The Scripto media does not exist.
            return false;
        }

        return $sMedia;
    }
}
