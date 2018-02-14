<?php
namespace Scripto\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class ScriptoProjectRepresentation extends AbstractEntityRepresentation
{
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
            'o:title' => $this->title(),
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
}
