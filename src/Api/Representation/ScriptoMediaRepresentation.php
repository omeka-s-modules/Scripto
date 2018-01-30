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

    public function getJsonLd()
    {
        $approvedBy = $this->approvedBy();
        $created = $this->created();
        $edited = $this->edited();
        return [
            'o-module-scripto:item' => $this->scriptoItem()->getReference(),
            'o:media' => $this->media()->getReference(),
            'o-module-scripto:is_completed' => $this->isCompleted(),
            'o-module-scripto:completedBy' => $this->completedBy(),
            'o-module-scripto:is_approved' => $this->isApproved(),
            'o-module-scripto:approvedBy' => $approvedBy ? $approvedBy->getReference() : null,
            'o:created' => $created ? $this->getDateTime($created) : null,
            'o:edited' => $edited ? $this->getDateTime($edited) : null,
        ];
    }

    public function getJsonLdType()
    {
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

    public function edited()
    {
        $sMedia = $this->resource->getScriptoMedia();
        return $sMedia ? $sMedia->getEdited() : null;
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

    /**
     * Get the most recent text.
     *
     * @return string
     */
    public function text()
    {
        $page = $this->resource->queryPage();
        return isset($page['revisions'][0]['content']) ? $page['revisions'][0]['content'] : null;
    }

    /**
     * Get text revisions.
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function revisions($limit = null, $offset = null)
    {
        return $this->resource->queryRevisions($limit, $offset);
    }

    /**
     * Is the corresponding MediaWiki page created?
     *
     * @return bool
     */
    public function mwPageIsCreated()
    {
        return $this->resource->pageIsCreated();
    }

    /**
     * Can the user perform this action on the corresponding MediaWiki page?
     *
     * @return bool
     */
    public function mwUserCan($action)
    {
        return $this->resource->userCan($action);
    }
}
