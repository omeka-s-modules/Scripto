<?php
namespace Scripto\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

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
        $adapter = $this->getAdapter();
        $totalCount = $adapter->getTotalScriptoMediaCount($this->id());
        if (!$totalCount) {
            return self::STATUS_APPROVED;
        }
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
