<?php
namespace Scripto\Job;

use DateTime;
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
            throw new Exception\RuntimeException('Cannot sync a project without an item set.');
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

        $sItemData = [];
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
            $query = $em->createQuery('
                DELETE FROM Scripto\Entity\ScriptoMedia sm
                WHERE sm.scriptoItem = :scripto_item_id
                AND sm.id NOT IN (:scripto_media_ids)
            ')->setParameters([
                'scripto_item_id' => $sItemId,
                'scripto_media_ids' => $sMediaIdsToRetain,
            ]);
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
     * Get all media assigned to the passed item, in original order.
     *
     * This method provides an abstraction for implementations that need to
     * change which media are mapped to an item.
     *
     * @param Item $item
     * @param ScriptoProject $project
     * @return array
     */
    public function getAllItemMedia(Item $item, ScriptoProject $project)
    {
        $conn = $this->getServiceLocator()->get('Omeka\Connection');
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');

        // Select all Document instances, including Image title.
        $instances = $conn->fetchAll('
            SELECT *
            FROM pwd_document_instance di
            LEFT JOIN value v ON v.resource_id = di.image_id
            WHERE di.document_id = ?
            AND di.image_id IS NOT NULL -- must have image
            AND v.value IS NOT NULL     -- image must have a title
            AND v.property_id = 1       -- dcterms:title
        ', [$item->getId()]);
        if (!$instances) {
            return [];
        }

        // Get the priority instance.
        $instance = $this->getPriorityInstance($instances);
        if (!$instance) {
            return [];
        }

        // Get the Document pages from the corresponding Image item.
        $imageItem = $em->find('Omeka\Entity\Item', $instance['image_id']);
        list($offset, $length) = $this->getOffsetAndLength($item, $instance, $conn);
        return array_slice($imageItem->getMedia()->toArray(), $offset, $length);
    }

    /**
     * Get a Document's instance, prioritized by source type and image series.
     *
     * A "collection" instance has priority because it is the most common.
     * Instances containing "a" series images have priority because they are
     * high quality images.
     *
     * This logic is identical to that used in the previous version of PWD.
     *
     * @param array $instances
     * @return array
     */
    public function getPriorityInstance(array $instances)
    {
        $sourceTypes = ['collection', 'publication']; // "microfilm" source has no images
        foreach ($sourceTypes as $sourceType) {
            foreach ($instances as $instance) {
                if ($sourceType === $instance['source_type'] && 'a' === substr($instance['value'], -1)) {
                    return $instance;
                }
            }
        }
        foreach ($sourceTypes as $sourceType) {
            foreach ($instances as $instance) {
                if ($sourceType === $instance['source_type']) {
                    return $instance;
                }
            }
        }
        return null;
    }

    /**
     * Get the page offset and length of a Document's instance.
     *
     * This logic is identical to that used in the previous version of PWD.
     *
     * @pa
     * @param array $instance
     * @param Connection $conn
     * @return array [offset, length]
     */
    public function getOffsetAndLength(Item $item, array $instance, $conn)
    {
        if (is_numeric($instance['page_number'])) {
            // The instance has a page number.
            $offset = $instance['page_number'] - 1;
            $length = is_numeric($instance['page_count']) ? $instance['page_count'] : null;
        } else {
            // The instance has no page_number. Query the document item for
            // bibo:pageStart and bibo:numPages and apply them to the offset and
            // length.
            $pageStart = $conn->fetchColumn('
                SELECT value
                FROM value
                WHERE resource_id = ?
                AND property_id = 111 -- bibo:pageStart
            ', [$item->getId()], 0);
            $numPages = $conn->fetchColumn('
                SELECT value
                FROM value
                WHERE resource_id = ?
                AND property_id = 106 -- bibo:numPages
            ', [$item->getId()], 0);

            $offset = $pageStart ? $pageStart - 1 : 0;
            $length = $numPages ?: null;
        }
        return [$offset, $length];
    }
}
