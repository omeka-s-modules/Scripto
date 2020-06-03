<?php
namespace Scripto\Job;

use DateTime;
use Doctrine\Common\Collections\Criteria;
use Omeka\Entity\Item;
use Omeka\Entity\Media;
use Omeka\Job\Exception;
use Scripto\Entity\ScriptoItem;
use Scripto\Entity\ScriptoMedia;
use Scripto\Entity\ScriptoProject;

/**
 * Sync a Scripto project with its corresponding item set.
 */
class SyncProject extends ScriptoJob
{
    public function perform()
    {
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $project = $em->find('Scripto\Entity\ScriptoProject', $this->getArg('scripto_project_id'));
        $this->syncProject($project);
    }

    /**
     * Sync a project.
     *
     * @param ScriptoProject $project
     */
    public function syncProject(ScriptoProject $project)
    {
        if (!$project->getItemSet()) {
            throw new Exception\RuntimeException('Cannot sync a project without an item set.'); // @translate
        }

        $this->syncProjectItems($project);
        $this->syncProjectMedia($project);

        $project->setSynced(new DateTime('now'));
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $em->merge($project); // entity is detached because of clear()
        $em->flush();
    }

    /**
     * Sync project items.
     *
     * Creates Scripto items that have been assigned to an item set since the
     * last sync; and deletes Scripto items that have been removed from an item
     * set since the last sync.
     *
     * @param ScriptoProject $project
     */
    public function syncProjectItems(ScriptoProject $project)
    {
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');

        // Get IDs of all items in the Scripto project.
        $sItems = $this->getProjectItemIds($project);

        // Get IDs of all items in the item set.
        $query = $em->createQuery('
            SELECT i.id
            FROM Omeka\Entity\Item i
            JOIN i.itemSets iset
            WHERE iset.id = :item_set_id'
        )->setParameter('item_set_id', $project->getItemSet()->getId());
        $oItems = array_column($query->getResult(), 'id');

        // Calculate which items to delete and which to create.
        $toDelete = array_diff($sItems, $oItems);
        $toCreate = array_diff($oItems, $sItems);

        foreach ($toCreate as $itemId) {
            $sItem = new ScriptoItem;
            $sItem->setScriptoProject($project);
            $sItem->setItem($em->getReference('Omeka\Entity\Item', $itemId));
            $em->persist($sItem);
        }

        // Delete removed Scripto items.
        $query = $em->createQuery('
            DELETE FROM Scripto\Entity\ScriptoItem si
            WHERE si.id IN (:scripto_item_ids)
        ')->setParameter('scripto_item_ids', array_keys($toDelete));
        $query->execute();

        // Flush the entity manager to complete the changes.
        $em->flush();
        $em->clear();
    }

    /**
     * Sync project media.
     *
     * Creates Scripto media that have been assigned to an item since the last
     * sync; and deletes Scripto media that have been removed from an item since
     * the last sync.
     *
     * @param ScriptoProject $project
     */
    public function syncProjectMedia(ScriptoProject $project)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');

        // Iterate all Scripto items in the Scripto project.
        $sItemIds = $this->getProjectItemIds($project);
        foreach ($sItemIds as $sItemId => $itemId) {
            $item = $em->getReference('Omeka\Entity\Item', $itemId);
            $sItem = $em->getReference('Scripto\Entity\ScriptoItem', $sItemId);
            $sMediaIdsToRetain = [];
            $position = 1;
            // Iterate all media assigned to the item.
            foreach ($this->getAllItemMedia($item, $project) as $media) {
                $sMedia = $this->getScriptoMediaEntity($project, $item, $media);
                if ($sMedia) {
                    // Scripto media already exists.
                    $sMediaIdsToRetain[] = $sMedia->getId();
                    if ($position !== $sMedia->getPosition()) {
                        // Position has changed; update the synced timestamp.
                        $sMedia->setSynced(new DateTime('now'));
                        $sMedia->setPosition($position);
                    }
                } else {
                    // Create new Scripto media.
                    $sMedia = new ScriptoMedia;
                    $sMedia->setScriptoItem($sItem);
                    $sMedia->setMedia($media);
                    $sMedia->setPosition($position);
                    $em->persist($sMedia);
                }
                $position++;
            }
            // Delete Scripto media assigned to this Scripto item that have not
            // been retained. Normally this step is not needed since deleting
            // a media will cascade delete all corresponding Scripto media.
            // Nevertheless, a bulk deletion will be necessary if the item/media
            // abstraction ever changes.
            if ($sMediaIdsToRetain) {
                $query = $em->createQuery('
                    DELETE FROM Scripto\Entity\ScriptoMedia sm
                    WHERE sm.scriptoItem = :scripto_item_id
                    AND sm.id NOT IN (:scripto_media_ids)
                ')->setParameters([
                    'scripto_item_id' => $sItemId,
                    'scripto_media_ids' => $sMediaIdsToRetain,
                ]);
            } else {
                $query = $em->createQuery('
                    DELETE FROM Scripto\Entity\ScriptoMedia sm
                    WHERE sm.scriptoItem = :scripto_item_id
                ')->setParameters([
                    'scripto_item_id' => $sItemId,
                ]);
            }

            $query->execute();

            // Must flush the entity manager after deleting so newly persisted
            // entities are not deleted.
            $em->flush();
            $em->clear();
        }
    }

    /**
     * Get a Scripto media entity given project, item, and media IDs.
     *
     * @param ScriptoProject $project
     * @param Item $item
     * @param Media $media
     * @return ScriptoMedia|null
     */
    public function getScriptoMediaEntity(ScriptoProject $project, Item $item, Media $media)
    {
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $query = $em->createQuery('
            SELECT m
            FROM Scripto\Entity\ScriptoMedia m
            JOIN m.scriptoItem i
            JOIN i.scriptoProject p
            WHERE m.media = :media_id
            AND i.item = :item_id
            AND p.id = :project_id'
        )->setParameters([
            'media_id' => $media->getId(),
            'item_id' => $item->getId(),
            'project_id' => $project->getId(),
        ]);
        try {
            $sMedia = $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $sMedia = null;
        }
        return $sMedia;
    }

    /**
     * Get media assigned to an item, in original order.
     *
     * This method provides an abstraction for implementations that need to
     * change which media are mapped to an item. These implementations should
     * honor the project's media type filter, if possible.
     *
     * @param Item $item
     * @param ScriptoProject $project
     * @return \Doctrine\Common\Collections\ArrayCollection|array
     */
    public function getAllItemMedia(Item $item, ScriptoProject $project)
    {
        $medias = $item->getMedia();
        $mediaTypes = $project->getMediaTypes();
        if ($mediaTypes) {
            // Filter media types.
            $orX = [Criteria::expr()->in('mediaType', $mediaTypes)];
            if (in_array('', $mediaTypes)) {
                $orX[] = Criteria::expr()->isNull('mediaType');
                $orX[] = Criteria::expr()->eq('mediaType', '');
            }
            $criteria = Criteria::create()->where(Criteria::expr()->orX(...$orX));
            $medias = $medias->matching($criteria);
        }
        return $medias;
    }
}
