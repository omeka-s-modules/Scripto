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

        $medias = [];
        foreach ($sItem->getItem()->getMedia() as $media) {
            $sMedia = $em->getRepository('Scripto\Entity\ScriptoMedia')->findOneBy([
                'scriptoItem' => $sItem->getId(),
                'media' => $media->getId(),
            ]);
            $medias[] = new ScriptoMediaResource($sItem, $media, $sMedia);
        }
        return new Response($medias);
    }

    public function create(Request $request)
    {
        $services = $this->getServiceLocator();
        $em = $services->get('Omeka\EntityManager');

        $data = $request->getContent();
        $sItem = $em->find('Scripto\Entity\ScriptoItem', $data['o-module-scripto:item']['o:id']);
        $media = $em->find('Omeka\Entity\Media', $data['o:media']['o:id']);

        $sMedia = new ScriptoMedia;
        $sMedia->setScriptoItem($sItem);
        $sMedia->setMedia($media);

        $em->persist($sMedia);
        $em->flush();
        return new Response(new ScriptoMediaResource($sItem, $media, $sMedia));
    }

    public function read(Request $request)
    {
        $services = $this->getServiceLocator();
        $em = $services->get('Omeka\EntityManager');

        list($projectId, $mediaId) = explode(':', $request->getId());
        $query = $em->createQuery('
            SELECT m
            FROM Scripto\Entity\ScriptoMedia m JOIN m.scriptoItem i JOIN i.scriptoProject p
            WHERE m.media = :media_id AND p.id = :project_id'
        );
        $query->setParameters([
            'media_id' => $mediaId,
            'project_id' => $projectId,
        ]);
        $sMedia = $query->getSingleResult();
        return new Response(new ScriptoMediaResource($sMedia->getScriptoItem(), $sMedia->getMedia(), $sMedia));

    }
}
