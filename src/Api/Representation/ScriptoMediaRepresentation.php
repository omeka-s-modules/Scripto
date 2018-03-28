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
    const STATUS_NEW = 'New'; // @translate
    const STATUS_IN_PROGRESS = 'In progress'; // @translate
    const STATUS_COMPLETED = 'Completed'; // @translate
    const STATUS_APPROVED = 'Approved'; // @translate

    /**
     * @var array Corresponding MediaWiki page information
     */
    protected $page;

    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/scripto-media-id',
            [
                'action' => $action,
                'project-id' => $this->resource->getScriptoItem()->getScriptoProject()->getId(),
                'item-id' => $this->resource->getScriptoItem()->getItem()->getId(),
                'media-id' => $this->resource->getMedia()->getId(),
            ],
            ['force_canonical' => $canonical]
        );
    }

    public function linkPretty($thumbnailType = 'square', $titleDefault = null,
        $action = null, array $attributes = null
    ) {
        $media = $this->media();
        $escape = $this->getViewHelper('escapeHtml');
        $thumbnail = $this->getViewHelper('thumbnail');
        $linkContent = sprintf(
            '%s<span class="resource-name">%s</span>',
            $thumbnail($media, $thumbnailType),
            $escape($media->displayTitle($titleDefault))
        );
        if (empty($attributes['class'])) {
            $attributes['class'] = 'resource-link';
        } else {
            $attributes['class'] .= ' resource-link';
        }
        return $this->linkRaw($linkContent, $action, $attributes);
    }

    public function getJsonLdType()
    {
        return 'o-module-scripto:Media';
    }

    public function getJsonLd()
    {
        $approvedBy = $this->approvedBy();
        $synced = $this->synced();
        $edited = $this->edited();
        $completed = $this->completed();
        $approved = $this->approved();
        return [
            'o-module-scripto:item' => $this->scriptoItem()->getReference(),
            'o:media' => $this->media()->getReference(),
            'o-module-scripto:edited_by' => $this->editedBy(),
            'o-module-scripto:completed_by' => $this->completedBy(),
            'o-module-scripto:approved_by' => $approvedBy ? $approvedBy->getReference() : null,
            'o-module-scripto:synced' => $synced ? $this->getDateTime($synced) : null,
            'o-module-scripto:edited' => $edited ? $this->getDateTime($edited) : null,
            'o-module-scripto:completed' => $completed ? $this->getDateTime($completed) : null,
            'o-module-scripto:approved' => $approved ? $this->getDateTime($approved) : null,
        ];
    }

    public function scriptoItem()
    {
        return $this->getAdapter('scripto_items')->getRepresentation($this->resource->getScriptoItem());
    }

    public function media()
    {
        return $this->getAdapter('media')->getRepresentation($this->resource->getMedia());
    }

    public function position()
    {
        return $this->resource->getPosition();
    }

    public function editedBy()
    {
        return $this->resource->getEditedBy();
    }

    public function completedBy()
    {
        return $this->resource->getCompletedBy();
    }

    public function approvedBy()
    {
        return $this->getAdapter('users')->getRepresentation($this->resource->getApprovedBy());
    }

    public function synced()
    {
        return $this->resource->getSynced();
    }

    public function edited()
    {
        return $this->resource->getEdited();
    }

    public function completed()
    {
        return $this->resource->getCompleted();
    }

    public function approved()
    {
        return $this->resource->getApproved();
    }

    /**
     * Get the previous Scripto media.
     *
     * @return ScriptoMediaRepresentation|null
     */
    public function previousScriptoMedia()
    {
        $previous = $this->getAdapter()->getPreviousScriptoMedia($this->resource);
        return $this->getAdapter()->getRepresentation($previous);
    }

    /**
     * Get the next Scripto media.
     *
     * @return ScriptoMediaRepresentation|null
     */
    public function nextScriptoMedia()
    {
        $next = $this->getAdapter()->getNextScriptoMedia($this->resource);
        return $this->getAdapter()->getRepresentation($next);
    }

    /**
     * Return the status of this media.
     *
     * - APPROVED: this Scripto media is approved (flagged by admin)
     * - COMPLETED: this Scripto media is completed (flagged by transcriber)
     * - IN PROGRESS: implied by an edited Scripto media entity
     * - NEW: implied by an unedited Scripto media entity
     *
     * @return int
     */
    public function status()
    {
        if ($this->approved()) {
            return self::STATUS_APPROVED;
        }
        if ($this->completed()) {
            return self::STATUS_COMPLETED;
        }
        if ($this->edited()) {
            return self::STATUS_IN_PROGRESS;
        }
        return self::STATUS_NEW;
    }

    /**
     * Was this media edited after it was approved?
     *
     * @return bool
     */
    public function isEditedAfterApproved()
    {
        return $this->edited() > $this->approved();
    }

    /**
     * Was this media edited after it was imported?
     *
     * Returns false if the project has not been imported.
     *
     * @return bool
     */
    public function isEditedAfterImported()
    {
        $imported = $this->resource->getScriptoItem()->getScriptoProject()->getImported();
        return $imported ? $this->edited() > $imported : false;
    }

    /**
     * Was this media synced after it was imported?
     *
     * Returns false if the project has not been imported.
     *
     * @return bool
     */
    public function isSyncedAfterImported()
    {
        $imported = $this->resource->getScriptoItem()->getScriptoProject()->getImported();
        return $imported ? $this->synced() > $imported : false;
    }

    /**
     * Get the title of the corresponding MediaWiki page.
     *
     * @return string
     */
    public function pageTitle()
    {
        return $this->resource->getMediawikiPageTitle();
    }

    /**
     * Get information about the corresponding MediaWiki page.
     *
     * Caches the information when first called.
     *
     * @return array
     */
    public function page()
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        if (null === $this->page) {
            $this->page = $client->queryPage($this->pageTitle());
        }
        return $this->page;
    }

    /**
     * Get the most recent wikitext.
     *
     * @return string|null
     */
    public function pageWikitext()
    {
        $page = $this->page();
        return isset($page['revisions'][0]['content']) ? $page['revisions'][0]['content'] : null;
    }

    /**
     * Get the most recent HTML.
     *
     * @return string|null
     */
    public function pageHtml()
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        return $client->parsePage($this->pageTitle());
    }

    /**
     * Get page revisions.
     *
     * @param int $limit
     * @param string $continue
     * @return array
     */
    public function pageRevisions($limit, $continue = null)
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        return $client->queryRevisions($this->pageTitle(), $limit, $continue);
    }

    /**
     * Get a page revision.
     *
     * @param int $revisionId
     * @return array
     */
    public function pageRevision($revisionId)
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        return $client->queryRevision($this->pageTitle(), $revisionId);
    }

    /**
     * Is the corresponding MediaWiki page created?
     *
     * @return bool
     */
    public function pageIsCreated()
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        return $client->pageIsCreated($this->page());
    }

    /**
     * Can the user perform this action on the corresponding MediaWiki page?
     *
     * @return bool
     */
    public function userCan($action)
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        return $client->userCan($this->page(), $action);
    }

    /**
     * Is the current user watching the corresponding MediaWiki page?
     *
     * @return bool
     */
    public function isWatched()
    {
        $page = $this->page();
        return (bool) $page['watched'];
    }

    /**
     * Get the user level at which the corresponding MediaWiki page can be edited.
     *
     * Note that, unlike MediaWiki, Scripto does not differentiate between
     * "create" and "edit" protection types when restricting access to a page.
     *
     * @return string "sysop", "autoconfirmed", "all" (for no restrictions)
     */
    public function restrictionLevel()
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        $type = $this->pageIsCreated() ? 'edit' : 'create';
        return $client->getPageProtectionLevel($this->page(), $type);
    }
}
