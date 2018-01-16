<?php
namespace Scripto\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class ScriptoItemRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLdType()
    {
        return 'o-module-scripto:Item';
    }

    public function getJsonLd()
    {
        return [
            'o-module-scripto:project' => $this->project()->getReference(),
            'o:item' => $this->item()->getReference(),
            'o-module-scripto:media' => $this->media(),
        ];
    }

    public function project()
    {
        return $this->getAdapter('scripto_projects')
            ->getRepresentation($this->resource->getProject());
    }

    public function item()
    {
        return $this->getAdapter('items')
            ->getRepresentation($this->resource->getItem());
    }

    public function created()
    {
        return $this->resource->getCreated();
    }

    public function modified()
    {
        return $this->resource->getModified();
    }
}
