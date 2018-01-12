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
    }

    public function item()
    {
        return $this->getAdapter('scripto_items')->getRepresentation($this->sItem);
    }

    public function media()
    {
        return $this->getAdapter('media')->getRepresentation($this->oMedia);
    }
}
