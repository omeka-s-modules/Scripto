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
            'o-module-scripto:project' => $this->scriptoProject()->getReference(),
            'o:item' => $this->item()->getReference(),
        ];
    }

    public function scriptoProject()
    {
        return $this->getAdapter('scripto_projects')
            ->getRepresentation($this->resource->getScriptoProject());
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
