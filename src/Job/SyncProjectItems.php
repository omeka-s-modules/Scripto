<?php
namespace Scripto\Job;

use Omeka\Job\AbstractJob;

class SyncProjectItems extends AbstractJob
{
    /**
     * Sync project items with the corresponding item set.
     *
     * Deletes Scripto items that have been removed from the item set since the
     * last sync; and creates Scripto items that have been assigned to the item
     * set since the last sync.
     */
    public function perform()
    {
        $projectId = $this->getArg('scripto_project_id');
        $itemSetId = $this->getArg('item_set_id');

        $services = $this->getServiceLocator();
        $em = $services->get('Omeka\EntityManager');
        $api = $services->get('Omeka\ApiManager');

        // Get IDs of all items in the Scripto project.
        $query = $em->createQuery('
            SELECT si.id scripto_item_id, i.id item_id
            FROM Scripto\Entity\ScriptoItem si
            JOIN si.item i
            JOIN si.scriptoProject sp
            WHERE sp.id = :scripto_project_id'
        )->setParameter('scripto_project_id', $projectId);
        $sItems = array_column($query->getScalarResult(), 'item_id', 'scripto_item_id');

        // Get IDs of all items in the item set.
        $query = $em->createQuery('
            SELECT i.id
            FROM Omeka\Entity\Item i
            JOIN i.itemSets iset
            WHERE iset.id = :item_set_id'
        )->setParameter('item_set_id', $itemSetId);
        $oItems = array_column($query->getScalarResult(), 'id');

        // Calculate which items to delete and which to create.
        $toDelete = array_diff($sItems, $oItems);
        $toCreate = array_diff($oItems, $sItems);

        $sItemData = [];
        foreach ($toCreate as $oItemId) {
            $sItemData[] = [
                'o-module-scripto:project' => ['o:id' => $projectId],
                'o:item' => ['o:id' => $oItemId],
            ];
        }

        // Batch delete and create the Scripto items. Note that array_diff()
        // preserved scripto_item_id keys in $toDelete.
        $api->batchDelete('scripto_items', array_keys($toDelete));
        $api->batchCreate('scripto_items', $sItemData);
    }
}
