<?php
namespace Scripto\Api\Representation;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Representation\AbstractResourceRepresentation;
use Scripto\Api\ScriptoMediaResource;
use Zend\ServiceManager\ServiceLocatorInterface;

class ScriptoMediaRepresentation extends AbstractResourceRepresentation
{
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
            'o-module-scripto:completed' => $this->completed(),
            'o-module-scripto:completedBy' => $this->completedBy(),
            'o-module-scripto:approved' => $this->approved(),
            'o-module-scripto:approvedBy' => $approvedBy ? $approvedBy->getReference() : null,
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

    public function completed()
    {
        return $this->sMedia ? $this->sMedia->getCompleted() : false;
    }

    public function completedBy()
    {
        return $this->sMedia ? $this->sMedia->getCompletedBy() : null;
    }

    public function approved()
    {
        return $this->sMedia ? $this->sMedia->getApproved() : false;
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
}
