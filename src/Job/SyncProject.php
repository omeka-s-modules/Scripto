<?php
namespace Scripto\Job;

use Omeka\Entity\Item;
use Omeka\Job\AbstractJob;
use Scripto\Entity\ScriptoItem;
use Scripto\Entity\ScriptoMedia;

class SyncProject extends AbstractJob
{
    /**
     * Sync a Scripto project with its corresponding item set.
     */
    public function perform()
    {
        $projectId = $this->getArg('scripto_project_id');
        $itemSetId = $this->getArg('item_set_id');
        $this->syncProjectItems($projectId);
        $this->syncProjectMedia($projectId);
    }

    /**
     * Sync project items.
     *
     * Deletes Scripto items that have been removed from the item set since the
     * last sync; and creates Scripto items that have been assigned to the item
     * set since the last sync.
     *
     * @param int $projectId
     */
    public function syncProjectItems($projectId)
    {
        $services = $this->getServiceLocator();
        $em = $services->get('Omeka\EntityManager');

        $project = $em->find('Scripto\Entity\ScriptoProject', $projectId);

        // Get IDs of all items in the Scripto project.
        $sItems = $this->getProjectItemIds($project->getId());

        // Get IDs of all items in the item set.
        $query = $em->createQuery('
            SELECT i.id
            FROM Omeka\Entity\Item i
            JOIN i.itemSets iset
            WHERE iset.id = :item_set_id'
        )->setParameter('item_set_id', $project->getItemSet()->getId());
        $oItems = array_column($query->getScalarResult(), 'id');

        // Calculate which items to delete and which to create.
        $toDelete = array_diff($sItems, $oItems);
        $toCreate = array_diff($oItems, $sItems);

        $sItemData = [];
        foreach ($toCreate as $itemId) {
            $sItem = new ScriptoItem;
            $sItem->setScriptoProject($project);
            $sItem->setItem($em->find('Omeka\Entity\Item', $itemId));
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
    }

    /**
     * Sync project media.
     *
     * Deletes Scripto media that have been removed from an item since the
     * last sync; and creates Scripto media that have been assigned to an item
     * since the last sync.
     *
     * @param int $projectId
     */
    public function syncProjectMedia($projectId)
    {
        $services = $this->getServiceLocator();
        $em = $services->get('Omeka\EntityManager');

        // Iterate all Scripto items in the Scripto project.
        $sItemIds = $this->getProjectItemIds($projectId);
        $sMediaIdsToRetain = [];
        foreach ($sItemIds as $sItemId => $itemId) {
            $item = $em->find('Omeka\Entity\Item', $itemId);
            $sItem = $em->find('Scripto\Entity\ScriptoItem', $sItemId);
            $position = 1;
            // Iterate all media assigned to the item.
            foreach ($this->getAllItemMedia($item) as $media) {
                $sMedia = $this->getScriptoMediaEntity($projectId, $item->getId(), $media->getId());
                if ($sMedia) {
                    // Update existing Scripto media.
                    $sMedia->setPosition($position);
                    $sMediaIdsToRetain[] = $sMedia->getId();
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
        }

        // Delete all Scripto media that have not been retained.
        $query = $em->createQuery('
            DELETE FROM Scripto\Entity\ScriptoMedia sm
            WHERE sm.id NOT IN (:scripto_media_ids)
        ')->setParameter('scripto_media_ids', $sMediaIdsToRetain);
        $query->execute();

        // Flush the entity manager after deleting so newly persisted entities
        // are not deleted.
        $em->flush();
    }

    /**
     * Get IDs of all items in the Scripto project.
     *
     * @param int $projectId
     * @return array
     */
    public function getProjectItemIds($projectId)
    {
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $query = $em->createQuery('
            SELECT si.id scripto_item_id, i.id item_id
            FROM Scripto\Entity\ScriptoItem si
            JOIN si.item i
            JOIN si.scriptoProject sp
            WHERE sp.id = :scripto_project_id'
        )->setParameter('scripto_project_id', $projectId);
        return array_column($query->getScalarResult(), 'item_id', 'scripto_item_id');
    }

    /**
     * Get a Scripto media entity given project, item, and media IDs.
     *
     * @param int $projectId
     * @param int $itemId
     * @param int $mediaId
     * @return ScriptoMedia|null
     */
    public function getScriptoMediaEntity($projectId, $itemId, $mediaId)
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
     * Get all media assigned to the passed item, in original order.
     *
     * This method provides an abstraction for implementations that need to
     * change which media are mapped to an item.
     *
     * @return array
     */
    public function getAllItemMedia(Item $item)
    {
        return $item->getMedia();
    }
}
