<?php
namespace Scripto\Api\Representation;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Representation\AbstractResourceRepresentation;
use Scripto\Api\ScriptoMediaResource;
use Zend\ServiceManager\ServiceLocatorInterface;

class ScriptoMediaRepresentation extends AbstractResourceRepresentation
{
    /**
     * Scripto media statuses
     */
    const STATUS_NEW = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_APPROVED = 3;

    public function getJsonLd(){
        $approvedBy = $this->approvedBy();
        $created = $this->created();
        $modified = $this->modified();
        return [
            'o-module-scripto:item' => $this->scriptoItem()->getReference(),
            'o:media' => $this->media()->getReference(),
            'o-module-scripto:is_completed' => $this->isCompleted(),
            'o-module-scripto:completedBy' => $this->completedBy(),
            'o-module-scripto:is_approved' => $this->isApproved(),
            'o-module-scripto:approvedBy' => $approvedBy ? $approvedBy->getReference() : null,
            'o:created' => $created ? $this->getDateTime($created) : null,
            'o:modified' => $modified ? $this->getDateTime($modified) : null,
        ];
    }

    public function getJsonLdType(){
        return 'o-module-scripto:Media';
    }

    public function scriptoItem()
    {
        return $this->getAdapter('scripto_items')->getRepresentation($this->resource->getScriptoItem());
    }

    public function media()
    {
        return $this->getAdapter('media')->getRepresentation($this->resource->getMedia());
    }

    public function isCompleted()
    {
        $sMedia = $this->resource->getScriptoMedia();
        return $sMedia ? $sMedia->getIsCompleted() : false;
    }

    public function completedBy()
    {
        $sMedia = $this->resource->getScriptoMedia();
        return $sMedia ? $sMedia->getCompletedBy() : null;
    }

    public function isApproved()
    {
        $sMedia = $this->resource->getScriptoMedia();
        return $sMedia ? $sMedia->getIsApproved() : false;
    }

    public function approvedBy()
    {
        $sMedia = $this->resource->getScriptoMedia();
        return $sMedia ? $this->getAdapter('users')->getRepresentation($sMedia->getApprovedBy()) : null;
    }

    public function created()
    {
        $sMedia = $this->resource->getScriptoMedia();
        return $sMedia ? $sMedia->getCreated() : null;
    }

    public function modified()
    {
        $sMedia = $this->resource->getScriptoMedia();
        return $sMedia ? $sMedia->getModified() : null;
    }

    /**
     * Return the status of this media.
     *
     * - APPROVED: this Scripto media is approved (flagged by admin)
     * - COMPLETED: this Scripto media is completed (flagged by transcriber)
     * - IN PROGRESS: implied by a created Scripto media entity
     * - NEW: implied by an uncreated Scripto media entity
     *
     * @return int
     */
    public function status()
    {
        if ($this->isApproved()) {
            return STATUS_APPROVED;
        }
        if ($this->isCompleted()) {
            return STATUS_COMPLETED;
        }
        if ($this->created()) {
            return STATUS_IN_PROGRESS;
        }
        return STATUS_NEW;
    }
}
