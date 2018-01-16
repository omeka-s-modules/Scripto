<?php
namespace Scripto\Api\Adapter;

use Omeka\Api\Adapter\AbstractAdapter;
use Omeka\Api\Request;
use Omeka\Api\Response;
use Scripto\Api\ScriptoMediaResource;
use Scripto\Entity\ScriptoMedia;

class ScriptoMediaAdapter extends AbstractAdapter
{
    public function getResourceName()
    {
        return 'scripto_media';
    }

    public function getRepresentationClass()
    {
        return 'Scripto\Api\Representation\ScriptoMediaRepresentation';
    }

    public function search(Request $request)
    {
        $services = $this->getServiceLocator();
        $em = $services->get('Omeka\EntityManager');

        $query = $request->getContent();
        $sItem = $em->find('Scripto\Entity\ScriptoItem', $query['scripto_item_id']);

        $media = [];
        foreach ($sItem->getItem()->getMedia() as $oMedia) {
            $sMedia = $em->getRepository('Scripto\Entity\ScriptoMedia')->findOneBy([
                'item' => $sItem->getId(),
                'media' => $oMedia->getId(),
            ]);
            $media[] = new ScriptoMediaResource($oMedia, $sItem, $sMedia);
        }
        return new Response($media);
    }

    public function create(Request $request)
    {
        $services = $this->getServiceLocator();
        $em = $services->get('Omeka\EntityManager');

        $data = $request->getContent();

        $sMedia = new ScriptoMedia;
        $sItem = $em->find('Scripto\Entity\ScriptoItem', $data['o-module-scripto:item']['o:id']);
        $sMedia->setItem($sItem);
        $oMedia = $em->find('Omeka\Entity\Media', $data['o:media']['o:id']);
        $sMedia->setMedia($oMedia);

        $em->persist($sMedia);
        $em->flush();
        return new Response(new ScriptoMediaResource($oMedia, $sItem, $sMedia));
    }
}
