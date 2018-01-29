<?php
namespace Scripto\Api\Adapter;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Exception;
use Omeka\Api\Request;
use Omeka\Api\Response;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\Item;
use Omeka\Entity\Media;
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
        throw new Exception\OperationNotImplementedException(
            'The Scripto\Api\Adapter\ScriptoMediaAdapter adapter does not implement the search operation.' // @translate
        );
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
        $sMedia = $this->getScriptoMediaEntity($request->getId());
        if ($sMedia) {
            // The Scripto media entity has already been created.
            $media = $sMedia->getMedia();
            $sItem = $sMedia->getScriptoItem();
        } else {
            // The Scripto media entity has not been created. Get the component
            // entities from the resource ID and verify that the media is
            // assigned to the item.
            list($projectId, $itemId, $mediaId) = explode(':', $request->getId());
            $media = $this->getAdapter('media')->findEntity($mediaId);
            $sItem = $this->getAdapter('scripto_items')->findEntity([
                'scriptoProject' => $projectId,
                'item' => $itemId,
            ]);
            if (!$this->itemHasMedia($sItem->getItem(), $media)) {
                throw new Exception\RuntimeException(sprintf(
                    'The specified media "%s" does not belong to the specified item "%s".',
                    $media->getId(),
                    $sItem->getItem()->getId()
                ));
            }
        }
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        return new Response(new ScriptoMediaResource($client, $sItem, $media, $sMedia));
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (Request::CREATE === $request->getOperation()) {
            if (!isset($data['o-module-scripto:text'])) {
                $errorStore->addError('o:media', 'A Scripto media must have text on creation.'); // @translate
            }
        }
        if (!isset($data['o-module-scripto:item']['o:id'])) {
            $errorStore->addError('o-module-scripto:item', 'A Scripto media must be assigned a Scripto item ID.'); // @translate
        }
        if (!isset($data['o:media']['o:id'])) {
            $errorStore->addError('o:media', 'A Scripto media must be assigned an Omeka media ID.'); // @translate
        }
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        if (Request::CREATE === $request->getOperation()) {
            $data = $request->getContent();

            // Can only set Scripto item and Omeka media during creation.
            $sItem = $this->getAdapter('scripto_items')->findEntity($data['o-module-scripto:item']['o:id']);
            $media = $this->getAdapter('media')->findEntity($data['o:media']['o:id']);

            $entity->setScriptoItem($sItem);
            $entity->setMedia($media);

            $resourceId = [$sItem->getScriptoProject()->getId(), $sItem->getItem()->getId(), $media->getId()];
            if ($this->getScriptoMediaEntity($resourceId)) {
                $errorStore->addError('o-module-scripto:media', 'Cannot create a Scripto media that has already been created.'); // @translate
            }
        }
        $entity->setText($request->getValue('o-module-scripto:text'));
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (null === $entity->getScriptoItem()) {
            $errorStore->addError('o-module-scripto:item', 'A Scripto item must not be null.'); // @translate
        }
        if (null === $entity->getMedia()) {
            $errorStore->addError('o:media', 'A media must not be null.'); // @translate
        }
    }

    /**
     * Get a Scripto media entity from a Scripto media resource ID.
     *
     * @param string|array $resourceId
     * @return ScriptoMedia|null
     */
    public function getScriptoMediaEntity($resourceId)
    {
        if (is_array($resourceId)) {
            $resourceId = implode(':', $resourceId);
        }
        if (!preg_match('/^\d+:\d+:\d+$/', $resourceId)) {
            throw new Exception\InvalidArgumentException('Invalid resource ID format; must use "scripto_project_id:item_id:media_id".'); // @translate
        }
        list($projectId, $itemId, $mediaId) = explode(':', $resourceId);
        $query = $this->getEntityManager()->createQuery('
            SELECT m
            FROM Scripto\Entity\ScriptoMedia m
            JOIN m.scriptoItem i
            JOIN i.scriptoProject p
            WHERE m.media = :media_id
            AND i.item = :item_id
            AND p.id = :project_id'
        )->setParameters([
            'media_id' => $mediaId,
            'item_id' => $itemId,
            'project_id' => $projectId,
        ]);
        try {
            $sMedia = $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $sMedia = null;
        }
        return $sMedia;
    }

    /**
     * Is this media assigned to this item?
     *
     * This method provides an abstraction for implementations that need to
     * change which media are assigned to an item.
     *
     * @param Item $item
     * @param Media $media
     * @return bool
     */
    public function itemHasMedia(Item $item, Media $media)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT COUNT(m.id)
            FROM Omeka\Entity\Media m
            WHERE m.id = :media_id
            AND m.item = :item_id'
        )->setParameters([
            'media_id' => $media->getId(),
            'item_id' => $item->getId(),
        ]);
        return (bool) $query->getSingleScalarResult();
    }
}
