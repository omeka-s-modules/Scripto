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
        $client = $services->get('Scripto\Mediawiki\ApiClient');

        $query = $request->getContent();
        $sItem = $em->find('Scripto\Entity\ScriptoItem', $query['scripto_item_id']);

        $medias = [];
        foreach ($sItem->getItem()->getMedia() as $media) {
            $sMedia = $em->getRepository('Scripto\Entity\ScriptoMedia')->findOneBy([
                'scriptoItem' => $sItem->getId(),
                'media' => $media->getId(),
            ]);
            $medias[] = new ScriptoMediaResource($client, $sItem, $media, $sMedia);
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
        $client = $services->get('Scripto\Mediawiki\ApiClient');

        list($projectId, $mediaId) = explode(':', $request->getId());

        // First, check if the Scripto media entity is already created. If not,
        // check if the given Omeka media belongs to an Omeka item that's
        // assigned to the given project.
        $query = $em->createQuery('
            SELECT m
            FROM Scripto\Entity\ScriptoMedia m JOIN m.scriptoItem i JOIN i.scriptoProject p
            WHERE m.media = :media_id AND p.id = :project_id'
        );
        $query->setParameters([
            'media_id' => $mediaId,
            'project_id' => $projectId,
        ]);
        try {
            $sMedia = $query->getSingleResult();
            $media = $sMedia->getMedia();
            $sItem = $sMedia->getScriptoItem();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $media = $em->find('Omeka\Entity\Media', $mediaId);
            $sItem = $em->getRepository('Scripto\Entity\ScriptoItem')->findOneBy([
                'scriptoProject' => $projectId,
                'item' => $media->getItem()->getId(),
            ]);
            $sMedia = null;
        }
        return new Response(new ScriptoMediaResource($client, $sItem, $media, $sMedia));

    }
}
