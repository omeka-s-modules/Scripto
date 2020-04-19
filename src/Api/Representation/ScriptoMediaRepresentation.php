<?php
namespace Scripto\Api\Representation;

use DateTime;
use Omeka\Api\Representation\AbstractEntityRepresentation;

class ScriptoMediaRepresentation extends AbstractEntityRepresentation
{
    /**
     * Scripto media statuses
     */
    const STATUS_NEW = 'New'; // @translate
    const STATUS_IN_PROGRESS = 'In progress'; // @translate
    const STATUS_COMPLETED = 'Completed'; // @translate
    const STATUS_APPROVED = 'Approved'; // @translate

    public function url($action = null, $canonical = false)
    {
        $url = parent::url($action, $canonical);
        if ($url) {
            return $url;
        }
        $urlHelper = $this->getViewHelper('Url');
        return $urlHelper(
            'scripto-media-id',
            [
                'action' => $action,
                'project-id' => $this->resource->getScriptoItem()->getScriptoProject()->getId(),
                'item-id' => $this->resource->getScriptoItem()->getItem()->getId(),
                'media-id' => $this->resource->getMedia()->getId(),
            ],
            ['force_canonical' => $canonical],
            true
        );
    }

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
            'o:position' => $this->position(),
            'o-module-scripto:synced' => $synced ? $this->getDateTime($synced) : null,
            'o-module-scripto:edited' => $edited ? $this->getDateTime($edited) : null,
            'o-module-scripto:edited_by' => $this->editedBy(),
            'o-module-scripto:completed' => $completed ? $this->getDateTime($completed) : null,
            'o-module-scripto:completed_by' => $this->completedBy(),
            'o-module-scripto:completed_revision' => $this->completedRevision(),
            'o-module-scripto:approved' => $approved ? $this->getDateTime($approved) : null,
            'o-module-scripto:approved_by' => $approvedBy ? $approvedBy->getReference() : null,
            'o-module-scripto:approved_revision' => $this->approvedRevision(),
            'o-module-scripto:imported_html' => $this->importedHtml(),
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

    public function synced()
    {
        return $this->resource->getSynced();
    }

    public function edited()
    {
        return $this->resource->getEdited();
    }

    public function editedBy()
    {
        return $this->resource->getEditedBy();
    }

    public function completed()
    {
        return $this->resource->getCompleted();
    }

    public function completedBy()
    {
        return $this->resource->getCompletedBy();
    }

    /**
     * Get the completed revision.
     *
     * Returns false if this media is marked as not complete. Returns the
     * revision ID if set. Returns the latest revision ID if the revision ID is
     * not set. Returns null if the page is not created (no revisions).
     *
     * @return int|null|false
     */
    public function completedRevision()
    {
        if (!$this->completed()) {
            return false;
        }
        $revisionId = $this->resource->getCompletedRevision();
        if ($revisionId) {
            return $revisionId;
        }
        $latestRevision = $this->pageLatestRevision(0);
        if ($latestRevision) {
            return $latestRevision['revid'];
        }
        return null;
    }

    public function approved()
    {
        return $this->resource->getApproved();
    }

    public function approvedBy()
    {
        return $this->getAdapter('users')->getRepresentation($this->resource->getApprovedBy());
    }

    /**
     * Get the approved revision.
     *
     * Returns false if this media is marked as not approved. Returns the
     * revision ID if set. Returns the latest revision ID if the revision ID is
     * not set. Returns null if the page is not created (no revisions).
     *
     * @return int|null|false
     */
    public function approvedRevision()
    {
        if (!$this->approved()) {
            return false;
        }
        $revisionId = $this->resource->getApprovedRevision();
        if ($revisionId) {
            return $revisionId;
        }
        $latestRevision = $this->pageLatestRevision(0);
        if ($latestRevision) {
            return $latestRevision['revid'];
        }
        return null;
    }

    public function importedHtml()
    {
        return $this->resource->getImportedHtml();
    }

    public function primaryMedia()
    {
        return $this->media();
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
     * - COMPLETED: this Scripto media is completed (flagged by content editor)
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
     * @param int $namespace The MediaWiki namespace
     * @return string
     */
    public function pageTitle($namespace)
    {
        if (1 === $namespace) {
            return sprintf('Talk:%s', $this->resource->getMediawikiPageTitle());
        } else {
            return $this->resource->getMediawikiPageTitle();
        }
    }

    /**
     * Get information about the corresponding MediaWiki page.
     *
     * Caches the information when first called.
     *
     * @param int $namespace The MediaWiki namespace
     * @return array
     */
    public function page($namespace)
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        return $client->queryPage($this->pageTitle($namespace));
    }

    /**
     * Get the latest revision from cached page.
     *
     * Use self::pageRevision() for more comprehensive revision info.
     *
     * @param int $namespace The MediaWiki namespace
     * @return array
     */
    public function pageLatestRevision($namespace)
    {
        $page = $this->page($namespace);
        return isset($page['revisions'][0]) ? $page['revisions'][0] : null;
    }

    /**
     * Get page wikitext.
     *
     * @param int $namespace The MediaWiki namespace
     * @param int $revisionId
     * @return string|null
     */
    public function pageWikitext($namespace, $revisionId = null)
    {
        $revision = $this->pageRevision($namespace, $revisionId);
        return $revision ? $revision['content'] : null;
    }

    /**
     * Get page HTML.
     *
     * @param int $namespace The MediaWiki namespace
     * @param int $revisionId
     * @return string|null
     */
    public function pageHtml($namespace, $revisionId = null)
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        $revision = $this->pageRevision($namespace, $revisionId);
        return $revision ? $client->parseRevision($revision['revid']) : null;
    }

    /**
     * Get page revisions.
     *
     * @param int $namespace The MediaWiki namespace
     * @param int $limit
     * @param string $continue
     * @return array
     */
    public function pageRevisions($namespace, $limit, $continue = null)
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        return $client->queryRevisions($this->pageTitle($namespace), $limit, $continue);
    }

    /**
     * Get the latest revision or an earlier one given a revision ID.
     *
     * When getting the latest revision, use this method instead of
     * self::latestRevision() for more comprehensive revision info.
     *
     * @param int $namespace The MediaWiki namespace
     * @param int|null $revisionId
     * @return array|null
     */
    public function pageRevision($namespace, $revisionId = null)
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        if (null === $revisionId) {
            // Get the latest revision.
            $latestRevision = $this->pageLatestRevision($namespace);
            return $latestRevision
                ? $client->queryRevision($this->pageTitle($namespace), $latestRevision['revid'])
                : null; // page not created (no revisions)
        } else {
            // Get a specific revision.
            return $client->queryRevision($this->pageTitle($namespace), $revisionId);
        }
    }

    /**
     * Is the corresponding MediaWiki page created?
     *
     * @param int $namespace The MediaWiki namespace
     * @return bool
     */
    public function pageIsCreated($namespace)
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        return $client->pageIsCreated($this->page($namespace));
    }

    /**
     * Can the user perform this action on the corresponding MediaWiki page?
     *
     * @param int $namespace The MediaWiki namespace
     * @return bool
     */
    public function userCan($namespace, $action)
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        return $client->userCan($this->page($namespace), $action);
    }

    /**
     * Can the user edit the corresponding MediaWiki page?
     *
     * @param int $namespace The MediaWiki namespace
     * @return bool
     */
    public function userCanEdit($namespace)
    {
        return $this->pageIsCreated($namespace) ? $this->userCan(0, 'edit') : $this->userCan(0, 'createpage');
    }

    /**
     * Is the current user watching the corresponding MediaWiki page?
     *
     * @param int $namespace The MediaWiki namespace
     * @return bool
     */
    public function isWatched($namespace)
    {
        $page = $this->page($namespace);
        return isset($page['watched']) ? $page['watched'] : false;
    }

    /**
     * Get edit access data from the corresponding MediaWiki page.
     *
     * Note that, unlike MediaWiki, Scripto does not differentiate between
     * "create" and "edit" protection types when restricting access to a page.
     *
     * @param int $namespace The MediaWiki namespace
     * @return array|null
     */
    public function editAccess($namespace)
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        $type = $this->pageIsCreated($namespace) ? 'edit' : 'create';
        $editAccess = $client->getPageProtection($this->page($namespace), $type);

        if ($editAccess) {
            if ('autoconfirmed' === $editAccess['level']) {
                $editAccess['label'] = 'Confirmed only'; // @translate
            }
            if ('sysop' === $editAccess['level']) {
                $editAccess['label'] = 'Admin only'; // @translate
            }
            // This protection has expired if the expiry date comes before the
            // current date.
            $editAccess['expired'] = $editAccess['expiry']
                ? $editAccess['expiry'] < new DateTime('now')
                : false;
        } else {
            $editAccess = [
                'type' => $type,
                'level' => 'all',
                'expiry' => null,
                'expired' => true,
                'label' => 'Open to all', // @translate
            ];
        }
        return $editAccess;
    }

    /**
     * Is the corresponding media a renderable image?
     *
     * The media must have an original file, the file size must be no more than
     * 40MB, and the file must be an image that can be renderable in browsers.
     *
     * @return bool
     */
    public function isRenderableImage()
    {
        $media = $this->media();
        if (!$media->hasOriginal()) {
            return false;
        }
        $sizeOk = 40000000 >= $media->size();
        $imageOk = in_array(
            $media->mediaType(),
            ['image/gif', 'image/jpeg', 'image/jpg', 'image/png']
        );
        return $sizeOk && $imageOk;
    }
}
