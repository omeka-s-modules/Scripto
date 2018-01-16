<?php
namespace Scripto\Api\Representation;

use Omeka\Entity\Media as OMedia;
use Omeka\Api\Representation\AbstractRepresentation;
use Scripto\Entity\ScriptoItem as SItem;
use Scripto\Entity\ScriptoMedia as SMedia;
use Zend\ServiceManager\ServiceLocatorInterface;

class ScriptoMediaRepresentation extends AbstractRepresentation
{
    /**
     * @var OMedia Omeka media
     */
    protected $oMedia;

    /**
     * @var SItem Scripto item
     */
    protected $sItem;

    /**
     * @var SMedia|null Scripto media
     */
    protected $sMedia;

    /**
     * @param ServiceLocatorInterface $services
     * @param OMedia $oMedia
     * @param SItem $sItem
     * @param SMedia|null $sMedia
     */
    public function __construct(ServiceLocatorInterface $services, OMedia $oMedia,
        SItem $sItem, SMedia $sMedia = null
    ) {
        $this->setServiceLocator($services);
        $this->oMedia = $oMedia;
        $this->sItem = $sItem;
        $this->sMedia = $sMedia;
    }

    public function jsonSerialize()
    {
        $approvedBy = $this->approvedBy();
        return [
            'o-module-scripto:item' => $this->item()->getReference(),
            'o:media' => $this->media()->getReference(),
            'o-module-scripto:completed' => $this->completed(),
            'o-module-scripto:completedBy' => $this->completedBy(),
            'o-module-scripto:approved' => $this->approved(),
            'o-module-scripto:approvedBy' => $approvedBy ? $approvedBy->getReference() : null,
        ];
    }

    public function item()
    {
        return $this->getAdapter('scripto_items')->getRepresentation($this->sItem);
    }

    public function media()
    {
        return $this->getAdapter('media')->getRepresentation($this->oMedia);
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
