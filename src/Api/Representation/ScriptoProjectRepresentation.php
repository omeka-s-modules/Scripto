<?php
namespace Scripto\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class ScriptoProjectRepresentation extends AbstractEntityRepresentation
{
    public function url($action = null, $canonical = false)
    {
        $url = parent::url($action, $canonical);
        if ($url) {
            return $url;
        }
        $urlHelper = $this->getViewHelper('Url');
        return $urlHelper(
            'scripto-project-id',
            [
                'action' => $action,
                'project-id' => $this->resource->getId(),
            ],
            ['force_canonical' => $canonical],
            true
        );
    }

    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/scripto-project-id',
            [
                'action' => $action,
                'project-id' => $this->resource->getId(),
            ],
            ['force_canonical' => $canonical]
        );
    }

    public function getJsonLdType()
    {
        return 'o-module-scripto:Project';
    }

    public function getJsonLd()
    {
        $owner = $this->owner();
        $itemSet = $this->itemSet();
        $property = $this->property();
        $synced = $this->synced();
        $imported = $this->imported();
        return [
            'o-module-scripto:title' => $this->title(),
            'o-module-scripto:description' => $this->description(),
            'o-module-scripto:guidelines' => $this->guidelines(),
            'o-module-scripto:create_account_text' => $this->createAccountText(),
            'o-module-scripto:reviewer' => $this->reviewers(),
            'o:is_public' => $this->isPublic(),
            'o:owner' => $owner ? $owner->getReference() : null,
            'o:item_set' => $itemSet ? $itemSet->getReference() : null,
            'o-module-scripto:media_types' => $this->mediaTypes() ?: null,
            'o:property' => $property ? $property->getReference() : null,
            'o:lang' => $this->lang(),
            'o-module-scripto:import_target' => $this->importTarget(),
            'o-module-scripto:browse_layout' => $this->browseLayout(),
            'o-module-scripto:filter_approved' => $this->filterApproved(),
            'o-module-scripto:item_type' => $this->itemType(),
            'o-module-scripto:media_type' => $this->mediaType(),
            'o-module-scripto:content_type' => $this->contentType(),
            'o:created' => $this->getDateTime($this->created()),
            'o-module-scripto:synced' => $synced ? $this->getDateTime($synced) : null,
            'o-module-scripto:imported' => $imported ? $this->getDateTime($imported) : null,
        ];
    }

    public function owner()
    {
        return $this->getAdapter('users')
            ->getRepresentation($this->resource->getOwner());
    }

    public function isPublic()
    {
        return $this->resource->getIsPublic();
    }

    public function itemSet()
    {
        return $this->getAdapter('item_sets')
            ->getRepresentation($this->resource->getItemSet());
    }

    public function mediaTypes()
    {
        return $this->resource->getMediaTypes() ?: [];
    }

    public function property()
    {
        return $this->getAdapter('properties')
            ->getRepresentation($this->resource->getProperty());
    }

    public function lang()
    {
        return $this->resource->getLang();
    }

    public function importTarget()
    {
        return $this->resource->getImportTarget();
    }

    public function title()
    {
        return $this->resource->getTitle();
    }

    public function description()
    {
        return $this->resource->getDescription();
    }

    public function guidelines()
    {
        return $this->resource->getGuidelines();
    }

    public function createAccountText()
    {
        return $this->resource->getCreateAccountText();
    }

    public function browseLayout()
    {
        return $this->resource->getBrowseLayout();
    }

    public function filterApproved()
    {
        return $this->resource->getFilterApproved();
    }

    public function itemType()
    {
        return $this->resource->getItemType();
    }

    public function mediaType()
    {
        return $this->resource->getMediaType();
    }

    public function contentType()
    {
        return $this->resource->getContentType();
    }

    public function reviewers()
    {
        $reviewers = [];
        foreach ($this->resource->getReviewers() as $reviewer) {
            $reviewers[] = new ScriptoReviewerRepresentation($reviewer, $this->getServiceLocator());
        }
        return $reviewers;
    }

    public function created()
    {
        return $this->resource->getCreated();
    }

    public function synced()
    {
        return $this->resource->getSynced();
    }

    public function imported()
    {
        return $this->resource->getImported();
    }

    public function primaryMedia()
    {
        $itemEntities = $this->resource->getItemSet()->getItems();
        if ($itemEntities->isEmpty()) {
            return null;
        }
        $itemEntity = $itemEntities->slice(0, 1)[0];
        $item = $this->getAdapter('items')->getRepresentation($itemEntity);
        return $item->primaryMedia();
    }

    /**
     * Get the number of items in this project.
     *
     * @return int
     */
    public function itemCount()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('scripto_items', [
            'scripto_project_id' => $this->id(),
            'limit' => 0,
        ]);
        return $response->getTotalResults();
    }

    /**
     * Get the number of items in this project that are approved.
     *
     * @return int
     */
    public function isApprovedItemCount()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('scripto_items', [
            'scripto_project_id' => $this->id(),
            'is_approved' => true,
            'limit' => 0,
        ]);
        return $response->getTotalResults();
    }

    /**
     * Get the number of items in this project that are not approved.
     *
     * @return int
     */
    public function isNotApprovedItemCount()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('scripto_items', [
            'scripto_project_id' => $this->id(),
            'is_not_approved' => true,
            'limit' => 0,
        ]);
        return $response->getTotalResults();
    }
}
