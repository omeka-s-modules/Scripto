<?php
namespace Scripto\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class ScriptoItemRepresentation extends AbstractEntityRepresentation
{
    /**
     * Scripto item statuses
     */
    const STATUS_NEW = 'New'; // @translate
    const STATUS_IN_PROGRESS = 'In progress'; // @translate
    const STATUS_COMPLETED = 'Completed'; // @translate
    const STATUS_APPROVED = 'Approved'; // @translate

    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/scripto/item',
            [
                'controller' => 'project',
                'action' => $action,
                'project-id' => $this->resource->getScriptoProject()->getId(),
                'item-id' => $this->resource->getItem()->getId(),
            ],
            ['force_canonical' => $canonical]
        );
    }

    public function getJsonLdType()
    {
        return 'o-module-scripto:Item';
    }

    public function getJsonLd()
    {
        $edited = $this->edited();
        return [
            'o-module-scripto:project' => $this->scriptoProject()->getReference(),
            'o:item' => $this->item()->getReference(),
            'o-module-scripto:synced' => $this->getDateTime($this->synced()),
            'o-module-scripto:edited' => $edited ? $this->getDateTime($edited) : null,
        ];
    }

    public function scriptoProject()
    {
        return $this->getAdapter('scripto_projects')
            ->getRepresentation($this->resource->getScriptoProject());
    }

    public function item()
    {
        return $this->getAdapter('items')
            ->getRepresentation($this->resource->getItem());
    }

    public function synced()
    {
        return $this->resource->getSynced();
    }

    public function edited()
    {
        return $this->resource->getEdited();
    }

    /**
     * Return the status of this item.
     *
     * The status is contingent on the status of child media.
     *
     * - APPROVED: implied by all Scripto media entities approved or no Scripto media entities
     * - COMPLETED: implied by all Scripto media entities completed
     * - IN PROGRESS: implied by at least one Scripto media entity edited
     * - NEW: implied by no Scripto media entities edited
     *
     * @return int
     */
    public function status()
    {
        $totalCount = $this->mediaCount();
        if (!$totalCount) {
            return self::STATUS_APPROVED;
        }
        if ($this->isApprovedMediaCount() === $totalCount) {
            return self::STATUS_APPROVED;
        }
        if ($this->isCompletedMediaCount() === $totalCount) {
            return self::STATUS_COMPLETED;
        }
        if ($this->isEditedMediaCount()) {
            return self::STATUS_IN_PROGRESS;
        }
        return self::STATUS_NEW;
    }

    /**
     * Get the number of child media.
     *
     * @return int
     */
    public function mediaCount()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('scripto_media', [
            'scripto_item_id' => $this->id(),
            'limit' => 0,
        ]);
        return $response->getTotalResults();
    }

    /**
     * Get the number of child media that are approved.
     *
     * @return int
     */
    public function isApprovedMediaCount()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('scripto_media', [
            'scripto_item_id' => $this->id(),
            'is_approved' => true,
            'limit' => 0,
        ]);
        return $response->getTotalResults();
    }

    /**
     * Get the number of child media that are completed.
     *
     * @return int
     */
    public function isCompletedMediaCount()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('scripto_media', [
            'scripto_item_id' => $this->id(),
            'is_completed' => true,
            'limit' => 0,
        ]);
        return $response->getTotalResults();
    }

    /**
     * Get the number of child media that are edited.
     *
     * @return int
     */
    public function isEditedMediaCount()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('scripto_media', [
            'scripto_item_id' => $this->id(),
            'is_edited' => true,
            'limit' => 0,
        ]);
        return $response->getTotalResults();
    }

    /**
     * Get the number of child media that have been edited after approved.
     *
     * @return int
     */
    public function isEditedAfterApprovedMediaCount()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('scripto_media', [
            'scripto_item_id' => $this->id(),
            'is_edited_after_approved' => true,
            'limit' => 0,
        ]);
        return $response->getTotalResults();
    }

    /**
     * Get the number of child media that have been edited after imported.
     *
     * @return int
     */
    public function isEditedAfterImportedMediaCount()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('scripto_media', [
            'scripto_item_id' => $this->id(),
            'is_edited_after_imported' => true,
            'limit' => 0,
        ]);
        return $response->getTotalResults();
    }

    /**
     * Get the number of child media that have been synced after imported.
     *
     * @return int
     */
    public function isSyncedAfterImportedMediaCount()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('scripto_media', [
            'scripto_item_id' => $this->id(),
            'is_synced_after_imported' => true,
            'limit' => 0,
        ]);
        return $response->getTotalResults();
    }
}
