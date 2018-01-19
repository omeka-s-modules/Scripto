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

    /**
     * @var ScriptoItem Scripto item
     */
    protected $sItem;

    /**
     * @var Media Omeka media
     */
    protected $media;

    /**
     * @var ScriptoMedia|null Scripto media
     */
    protected $sMedia;

    public function __construct(ScriptoMediaResource $resource, AdapterInterface $adapter) {
        parent::__construct($resource, $adapter);
        $this->sItem = $resource->getScriptoItem();
        $this->media = $resource->getMedia();
        $this->sMedia = $resource->getScriptoMedia();
    }

    public function getJsonLd(){
        $approvedBy = $this->approvedBy();
        return [
            'o-module-scripto:item' => $this->scriptoItem()->getReference(),
            'o:media' => $this->media()->getReference(),
            'o-module-scripto:is_completed' => $this->isCompleted(),
            'o-module-scripto:completedBy' => $this->completedBy(),
            'o-module-scripto:is_approved' => $this->isApproved(),
            'o-module-scripto:approvedBy' => $approvedBy ? $approvedBy->getReference() : null,
            'o:created' => $this->created() ? $this->getDateTime($this->created()) : null,
            'o:modified' => $this->modified() ? $this->getDateTime($this->modified()) : null,
        ];
    }

    public function getJsonLdType(){
        return 'o-module-scripto:Media';
    }

    public function scriptoItem()
    {
        return $this->getAdapter('scripto_items')->getRepresentation($this->sItem);
    }

    public function media()
    {
        return $this->getAdapter('media')->getRepresentation($this->media);
    }

    public function isCompleted()
    {
        return $this->sMedia ? $this->sMedia->getIsCompleted() : false;
    }

    public function completedBy()
    {
        return $this->sMedia ? $this->sMedia->getCompletedBy() : null;
    }

    public function isApproved()
    {
        return $this->sMedia ? $this->sMedia->getIsApproved() : false;
    }

    public function approvedBy()
    {
        return $this->sMedia
            ? $this->getAdapter('users')->getRepresentation($this->sMedia->getApprovedBy())
            : null;
    }

    public function created()
    {
        return $this->sMedia ? $this->sMedia->getCreated() : null;
    }

    public function modified()
    {
        return $this->sMedia ? $this->sMedia->getModified() : null;
    }

    /**
     * Return the status of this media.
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
