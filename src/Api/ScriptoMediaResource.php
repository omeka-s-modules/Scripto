<?php
namespace Scripto\Api;

use Omeka\Api\ResourceInterface;
use Omeka\Entity\Media as OMedia;
use Scripto\Entity\ScriptoItem as SItem;
use Scripto\Entity\ScriptoMedia as SMedia;

/**
 * Scripto media resource
 */
class ScriptoMediaResource implements ResourceInterface
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

    public function __construct(OMedia $oMedia, SItem $sItem, SMedia $sMedia = null)
    {
        $this->oMedia = $oMedia;
        $this->sItem = $sItem;
        $this->sMedia = $sMedia;
    }

    public function getId()
    {
        return $this->sMedia ? $this->sMedia->getId() : null;
    }

    public function getOMedia()
    {
        return $this->oMedia;
    }

    public function getSItem()
    {
        return $this->sItem;
    }

    public function getSMedia()
    {
        return $this->sMedia;
    }
}
