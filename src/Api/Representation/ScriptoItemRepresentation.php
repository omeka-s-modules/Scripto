<?php
namespace Scripto\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;
use Omeka\Entity\Item;
use Scripto\Api\ScriptoMediaResource;

class ScriptoItemRepresentation extends AbstractEntityRepresentation
{
    /**
     * Scripto item statuses
     */
    const STATUS_NEW = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_APPROVED = 3;

    public function getJsonLdType()
    {
        return 'o-module-scripto:Item';
    }

    public function getJsonLd()
    {
        return [
            'o-module-scripto:project' => $this->scriptoProject()->getReference(),
            'o:item' => $this->item()->getReference(),
            'o:created' => $this->getDateTime($this->created()),
            'o:modified' => $this->modified() ? $this->getDateTime($this->modified()) : null,
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

    public function created()
    {
        return $this->resource->getCreated();
    }

    public function modified()
    {
        return $this->resource->getModified();
    }

    /**
     * Return the status of this item.
     *
     * The status is contingent on the status of child media.
     *
     * - APPROVED: implied by all Scripto media entities approved
     * - COMPLETED: implied by all Scripto media entities completed
     * - IN PROGRESS: implied by at least one Scripto media entity edited
     * - NEW: implied by no Scripto media entities edited
     *
     * @return int
     */
    public function status()
    {
        $adapter = $this->getAdapter();
        $totalCount = $adapter->getTotalScriptoMediaCount($this->id());
        $approvedCount = $adapter->getApprovedScriptoMediaCount($this->id());
        if ($approvedCount === $totalCount) {
            return self::STATUS_APPROVED;
        }
        $completedCount = $adapter->getCompletedScriptoMediaCount($this->id());
        if ($completedCount === $totalCount) {
            return self::STATUS_COMPLETED;
        }
        $editedCount = $adapter->getEditedScriptoMediaCount($this->id());
        if ($editedCount) {
            return self::STATUS_IN_PROGRESS;
        }
        return self::STATUS_NEW;
    }
}
