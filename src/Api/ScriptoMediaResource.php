<?php
namespace Scripto\Api;

use Omeka\Api\ResourceInterface;
use Omeka\Entity\Media;
use Scripto\Entity\ScriptoItem;
use Scripto\Entity\ScriptoMedia;
use Scripto\Mediawiki\ApiClient;

/**
 * Scripto media API resource
 */
class ScriptoMediaResource implements ResourceInterface
{
    /**
     * @var ApiClient MediaWiki API client
     */
    protected $client;

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
     * @var array The corresponding MediaWiki page information
     */
    protected $page;

    /**
     * Construct the Scripto media API resource.
     *
     * @param ApiClient $client
     * @param ScriptoItem $sItem
     * @param Media $media
     * @param ScriptoMedia $sMedia
     */
    public function __construct(ApiClient $client, ScriptoItem $sItem,
        Media $media, ScriptoMedia $sMedia = null
    ) {
        $this->client = $client;
        $this->sItem = $sItem;
        $this->media = $media;
        $this->sMedia = $sMedia;
    }

    /**
     * Get the resource ID.
     *
     * Note that a Scripto media entity isn't created until the corresponding
     * MediaWiki page is created. Because of this we can't use the entity ID as
     * the resource ID. Instead we use an aggregate ID using Scripto project ID,
     * Omeka item ID, and Omeka media ID, each of which will always exist in
     * this context.
     *
     * @return int|null
     */
    public function getId()
    {
        return sprintf(
            '%s:%s:%s',
            $this->sItem->getScriptoProject()->getId(),
            $this->sItem->getItem()->getId(),
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

    /**
     * Get information about the corresponding MediaWiki page.
     *
     * Caches the information when first called.
     *
     * @return array
     */
    public function queryPage()
    {
        if (null === $this->page) {
            $this->page = $this->client->queryPage($this->getId());
        }
        return $this->page;
    }

    /**
     * Get revisions of the corresponding MediaWiki page.
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function queryRevisions($limit = null, $offset = null)
    {
        return $this->client->queryRevisions($this->getId(), $limit, $offset);
    }

    /**
     * Is the corresponding MediaWiki page created?
     *
     * Note that a MediaWiki page could have already been created if a
     * previously transcribed Omeka item has been removed from the project's
     * item set and subsequently re-added.
     *
     * @return bool
     */
    public function pageIsCreated()
    {
        $this->client->pageIsCreated($this->queryPage());
    }

    /**
     * Can the user perform this action on the corresponding MediaWiki page?
     *
     * @return bool
     */
    public function userCan($action)
    {
        $this->client->userCan($this->queryPage(), $action);
    }
}
