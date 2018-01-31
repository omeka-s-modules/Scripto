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
     * @var array Corresponding MediaWiki page information
     */
    protected $mwPage;

    public function getJsonLd()
    {
        $approvedBy = $this->approvedBy();
        $created = $this->created();
        $edited = $this->edited();
        return [
            'o-module-scripto:item' => $this->scriptoItem()->getReference(),
            'o:media' => $this->media()->getReference(),
            'o-module-scripto:is_completed' => $this->isCompleted(),
            'o-module-scripto:completedBy' => $this->completedBy(),
            'o-module-scripto:is_approved' => $this->isApproved(),
            'o-module-scripto:approvedBy' => $approvedBy ? $approvedBy->getReference() : null,
            'o:created' => $created ? $this->getDateTime($created) : null,
            'o:edited' => $edited ? $this->getDateTime($edited) : null,
        ];
    }

    public function getJsonLdType()
    {
        return 'o-module-scripto:Media';
    }

    public function scriptoItem()
    {
        return $this->getAdapter('scripto_items')->getRepresentation($this->resource->getScriptoItem());
    }

    public function media()
    {
        return $this->getAdapter('media')->getRepresentation($this->resource->getMedia());
    }

    public function isCompleted()
    {
        return $this->resource->getIsCompleted();
    }

    public function completedBy()
    {
        return $this->resource->getCompletedBy();
    }

    public function isApproved()
    {
        return $this->resource->getIsApproved();
    }

    public function approvedBy()
    {
        return $this->getAdapter('users')->getRepresentation($this->resource->getApprovedBy());
    }

    public function created()
    {
        return $this->resource->getCreated();
    }

    public function edited()
    {
        return $this->resource->getEdited();
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
        if ($this->isApproved()) {
            return STATUS_APPROVED;
        }
        if ($this->isCompleted()) {
            return STATUS_COMPLETED;
        }
        if ($this->edited()) {
            return STATUS_IN_PROGRESS;
        }
        return STATUS_NEW;
    }

    /**
     * Get information about the corresponding MediaWiki page.
     *
     * Caches the information when first called.
     *
     * @return array
     */
    public function mwPage()
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        if (null === $this->page) {
            $this->mwPage = $client->queryPage($this->resource->getMediawikiPageTitle());
        }
        return $this->mwPage;
    }

    /**
     * Get the most recent text.
     *
     * @return string
     */
    public function text()
    {
        $mwPage = $this->mwPage();
        return isset($mwPage['revisions'][0]['content']) ? $mwPage['revisions'][0]['content'] : null;
    }

    /**
     * Get text revisions.
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function revisions($limit = null, $offset = null)
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        return $client->queryRevisions($this->resource->getMediawikiPageTitle(), $limit, $offset);
    }

    /**
     * Is the corresponding MediaWiki page created?
     *
     * @return bool
     */
    public function mwPageIsCreated()
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        return $client->pageIsCreated($this->mwPage());
    }

    /**
     * Can the user perform this action on the corresponding MediaWiki page?
     *
     * @return bool
     */
    public function mwUserCan($action)
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        return $client->userCan($this->mwPage(), $action);
    }
}
