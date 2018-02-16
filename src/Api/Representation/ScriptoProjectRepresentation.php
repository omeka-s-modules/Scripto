<?php
namespace Scripto\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class ScriptoProjectRepresentation extends AbstractEntityRepresentation
{
    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/scripto/id',
            [
                'controller' => 'project',
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
            'o:owner' => $owner ? $owner->getReference() : null,
            'o:item_set' => $itemSet ? $itemSet->getReference() : null,
            'o:property' => $property ? $property->getReference() : null,
            'o:lang' => $this->lang(),
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

    public function itemSet()
    {
        return $this->getAdapter('item_sets')
            ->getRepresentation($this->resource->getItemSet());
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

    public function title()
    {
        return $this->resource->getTitle();
    }

    public function description()
    {
        return $this->resource->getDescription();
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
