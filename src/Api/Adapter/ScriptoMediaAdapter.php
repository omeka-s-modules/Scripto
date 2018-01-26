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
 * ScriptoMediaResource and because the corresponding MediaWiki pages must be
 * edited before flushing the entity manager during create/update.
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
            'The Scripto\Api\Adapter\ScriptoMediaAdapter adapter does not implement the search operation' // @translate
        );
    }

    public function create(Request $request)
    {
        $sMedia = new ScriptoMedia;
        $this->hydrateEntity($request, $sMedia, new ErrorStore);
        $this->editMediawikiPage($sMedia, $request->getValue('o-module-scripto:text'));
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
                    'The specified media "%s" does not belong to the specified item "%s"',
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
        if (Request::CREATE === $request->getOperation()) {
            $data = $request->getContent();
            if (!isset($data['o-module-scripto:text'])) {
                $errorStore->addError('o:media', 'A Scripto media must have text on creation.'); // @translate
            }
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

    /**
     * Get a Scripto media entity from a Scripto media resource ID.
     *
     * @param string $resourceId
     * @return ScriptoMedia|null
     */
    public function getScriptoMediaEntity($resourceId)
    {
        if (!preg_match('/^\d+:\d+:\d+$/', $resourceId)) {
            throw new Exception\RuntimeException('Invalid resource ID format; must use "scripto_project_id:item_id:media_id"'); // @translate
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
     * Get the Scripto media resource ID from the Scripto media entity.
     *
     * @param ScriptoMedia $sMedia
     * @return string
     */
    public function getScriptoMediaResourceId(ScriptoMedia $sMedia)
    {
        return sprintf(
            '%s:%s:%s',
            $sMedia->getScriptoItem()->getScriptoProject()->getId(),
            $sMedia->getScriptoItem()->getItem()->getId(),
            $sMedia->getMedia()->getId()
        );
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

    /**
     * Edit a corresponding MediaWiki page.
     *
     * @param ScriptoMedia $sMedia
     * @param string $text
     */
    public function editMediawikiPage(ScriptoMedia $sMedia, $text)
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        $pageTitle = $this->getScriptoMediaResourceId($sMedia);
        $page = $client->queryPage($pageTitle);
        $pageIsCreated = $client->pageIsCreated($page);
        if (!$pageIsCreated && !$client->userCan($page, 'createpage')) {
            throw new Exception\RuntimeException(sprintf(
                $this->getTranslator()->translate('The MediaWiki user does not have the necessary permissions to create the page "%s"'),
                $pageTitle
            ));
        }
        if ($pageIsCreated && !$client->userCan($page, 'edit')) {
            throw new Exception\RuntimeException(sprintf(
                $this->getTranslator()->translate('The MediaWiki user does not have the necessary permissions to edit the page "%s"'),
                $pageTitle
            ));
        }
        $client->editPage($pageTitle, $text);
    }
}
