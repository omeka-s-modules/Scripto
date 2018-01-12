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

    /**
     * Get this Scripto item's media.
     *
     * @return array
     */
    public function media()
    {
        $services = $this->getServiceLocator();
        $em = $services->get('Omeka\EntityManager');

        $media = [];
        foreach ($this->resource->getItem()->getMedia() as $oMedia) {
            $sItem = $this->resource;
            $sMedia = $em->find('Scripto\Entity\ScriptoMedia', $oMedia->getId());
            $media[]= new ScriptoMediaRepresentation($services, $oMedia, $sItem, $sMedia);
        }
        return $media;
    }
}
