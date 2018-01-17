<?php
namespace Scripto\Api;

use Omeka\Api\ResourceInterface;
use Omeka\Entity\Media;
use Scripto\Entity\ScriptoItem;
use Scripto\Entity\ScriptoMedia;

/**
 * Scripto media API resource
 */
class ScriptoMediaResource implements ResourceInterface
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

    /**
     * Construct the Scripto media API resource.
     *
     * @param ScriptoItem $sItem
     * @param Media $media
     * @param ScriptoMedia $sMedia
     */
    public function __construct(ScriptoItem $sItem, Media $media, ScriptoMedia $sMedia = null)
    {
        $this->sItem = $sItem;
        $this->media = $media;
        $this->sMedia = $sMedia;
    }

    /**
     * Get the resource ID.
     *
     * Note that a Scripto media entity isn't created until the corresponding
     * MediaWiki page is created. Because of this we can't use the entity ID as
     * the resource ID. Instead we use an aggregate ID using Scripto project ID
     * and Omeka media ID, both of which will always exist in this context.
     *
     * @return int|null
     */
    public function getId()
    {
        return sprintf(
            '%s:%s',
            $this->sItem->getScriptoProject()->getId(),
            $this->media->getId()
        );
    }

    /**
     * Get the Scripto item.
     *
     * @return ScriptoItem
     */
    public function getScriptoItem()
    {
        return $this->sItem;
    }

    /**
     * Get the Omeka media.
     *
     * @return Media
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Get the Scripto media, if created.
     *
     * @return ScriptoMedia|null
     */
    public function getScriptoMedia()
    {
        return $this->sMedia;
    }
}
