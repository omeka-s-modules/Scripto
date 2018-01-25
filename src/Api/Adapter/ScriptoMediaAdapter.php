<?php
namespace Scripto\Api\Adapter;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Exception;
use Omeka\Api\Request;
use Omeka\Api\Response;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Scripto\Api\ScriptoMediaResource;
use Scripto\Entity\ScriptoMedia;

/**
 * Scripto media adapter
 *
 * Must override SCRUD operations because of the unconventional construction of
 * ScriptoMediaResource.
 */
class ScriptoMediaAdapter extends AbstractEntityAdapter
{
    public function getResourceName()
    {
        return 'scripto_media';
    }

    public function getRepresentationClass()
    {
        return 'Scripto\Api\Representation\ScriptoMediaRepresentation';
    }

    public function getEntityClass()
    {
        return 'Scripto\Entity\ScriptoMedia';
    }

    public function search(Request $request)
    {
        $services = $this->getServiceLocator();
        $em = $services->get('Omeka\EntityManager');
        $client = $services->get('Scripto\Mediawiki\ApiClient');

        $sItemId = $request->getValue('scripto_item_id');
        if (!$sItemId) {
            throw new Exception\BadRequestException('The search query must include scripto_item_id'); // @translate
        }
        $sItem = $this->getAdapter('scripto_items')->findEntity($sItemId);

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
        $sMedia = new ScriptoMedia;
        $this->hydrateEntity($request, $sMedia, new ErrorStore);
        $this->getEntityManager()->persist($sMedia);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->refresh($sMedia);

        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        return new Response(new ScriptoMediaResource(
            $client, $sMedia->getScriptoItem(), $sMedia->getMedia(), $sMedia
        ));
    }

    public function read(Request $request)
    {
        $services = $this->getServiceLocator();
        $em = $services->get('Omeka\EntityManager');
        $client = $services->get('Scripto\Mediawiki\ApiClient');

        if (!$this->resourceIdIsValid($request->getId())) {
            throw new Exception\BadRequestException('Invalid resource ID format; must use "scripto_project_id:media_id"'); // @translate
        }
        list($projectId, $mediaId) = explode(':', $request->getId());

        // First, check if the Scripto media entity is already created. If not,
        // check if the given Omeka media belongs to an Omeka item that's
        // assigned to the given project.
        $query = $em->createQuery('
            SELECT m
            FROM Scripto\Entity\ScriptoMedia m
            JOIN m.scriptoItem i
            JOIN i.scriptoProject p
            WHERE m.media = :media_id
            AND p.id = :project_id'
        )->setParameters([
            'media_id' => $mediaId,
            'project_id' => $projectId,
        ]);
        try {
            // The Scripto media entity exists.
            $sMedia = $query->getSingleResult();
            $media = $sMedia->getMedia();
            $sItem = $sMedia->getScriptoItem();
        } catch (\Doctrine\ORM\NoResultException $e) {
            // The Scripto media entity does not exist.
            $media = $this->getAdapter('media')->findEntity($mediaId);
            $sItem = $this->getAdapter('scripto_items')->findEntity([
                'scriptoProject' => $projectId,
                'item' => $media->getItem()->getId(),
            ]);
            $sMedia = null;
        }
        return new Response(new ScriptoMediaResource($client, $sItem, $media, $sMedia));
    }

    /**
     * Is the passed Scripto media resource ID valid?
     *
     * @param int $id
     * @return bool
     */
    public function resourceIdIsValid($id)
    {
        return preg_match('/^\d+:\d$/', $id); // scripto_project_id:media_id
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        if (Request::CREATE === $request->getOperation()) {
            $data = $request->getContent();
            if (!isset($data['o-module-scripto:item']['o:id'])) {
                $errorStore->addError('o-module-scripto:item', 'A Scripto media must be assigned a Scripto item on creation.'); // @translate
            }
            if (!isset($data['o:media']['o:id'])) {
                $errorStore->addError('o:media', 'A Scripto media must be assigned an Omeka media on creation.'); // @translate
            }
        }
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        if (Request::CREATE === $request->getOperation()) {
            $data = $request->getContent();

            $sItem = $this->getAdapter('scripto_items')->findEntity($data['o-module-scripto:item']['o:id']);
            $media = $this->getAdapter('media')->findEntity($data['o:media']['o:id']);

            $entity->setScriptoItem($sItem);
            $entity->setMedia($media);
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (null === $entity->getScriptoItem()) {
            $errorStore->addError('o-module-scripto:item', 'A Scripto item must not be null'); // @translate
        }
        if (null === $entity->getMedia()) {
            $errorStore->addError('o:media', 'A media must not be null'); // @translate
        }
    }
}
