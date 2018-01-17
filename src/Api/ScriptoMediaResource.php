<?php
namespace Scripto\Api;

use Omeka\Api\ResourceInterface;
use Omeka\Entity\Media as OMedia;
use Scripto\Entity\ScriptoItem as SItem;
use Scripto\Entity\ScriptoMedia as SMedia;

/**
 * Scripto media API resource
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

    /**
     * Construct the Scripto media API resource.
     *
     * @param OMedia $oMedia
     * @param SItem $sItem
     * @param SMedia $sMedia
     */
    public function __construct(OMedia $oMedia, SItem $sItem, SMedia $sMedia = null)
    {
        $this->oMedia = $oMedia;
        $this->sItem = $sItem;
        $this->sMedia = $sMedia;
    }

    /**
     * Get the resource ID.
     *
     * @return int|null
     */
    public function getId()
    {
        return sprintf(
            '%s:%s:%s',
            $this->sItem->getProject()->getId(),
            $this->sItem->getItem()->getId(),
            $this->oMedia->getId()
        );
    }

    /**
     * Get the Omeka media.
     *
     * @return OMedia
     */
    public function getOMedia()
    {
        return $this->oMedia;
    }

    /**
     * Get the Scripto item.
     *
     * @return SItem
     */
    public function getSItem()
    {
        return $this->sItem;
    }

    /**
     * Get the Scripto media, if created.
     *
     * @return SMedia|null
     */
    public function getSMedia()
    {
        return $this->sMedia;
    }
}
