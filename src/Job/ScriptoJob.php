<?php
namespace Scripto\Job;

use Omeka\Job\AbstractJob;
use Omeka\Job\Exception;
use Scripto\Entity\ScriptoProject;

abstract class ScriptoJob extends AbstractJob
{
    /**
     * Get IDs of all items in the Scripto project.
     *
     * @param ScriptoProject $project
     * @return array
     */
    public function getProjectItemIds(ScriptoProject $project)
    {
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $query = $em->createQuery('
            SELECT si.id scripto_item_id, i.id item_id
            FROM Scripto\Entity\ScriptoItem si
            JOIN si.item i
            JOIN si.scriptoProject sp
            WHERE sp.id = :scripto_project_id'
        );
        $conn = $em->getConnection();
        $stmt = $conn->prepare($query->getSQL());
        $stmt->bindValue(1, $project->getId());
        $stmt->execute();
        $results = [];
        foreach ($stmt as $row) {
            $results[$row['id_0']] = $row['id_1'];
        }
        return $results;
    }

    /**
     * Get IDs of all media in the Scripto project.
     *
     * @param ScriptoProject $project
     * @return array
     */
    public function getProjectMediaIds(ScriptoProject $project)
    {
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $query = $em->createQuery('
            SELECT sm.id scripto_media_id, m.id media_id
            FROM Scripto\Entity\ScriptoMedia sm
            JOIN sm.media m
            JOIN sm.scriptoItem si
            JOIN si.scriptoProject sp
            WHERE sp.id = :scripto_project_id'
        );
        $conn = $em->getConnection();
        $stmt = $conn->prepare($query->getSQL());
        $stmt->bindValue(1, $project->getId());
        $stmt->execute();
        $results = [];
        foreach ($stmt as $row) {
            $results[$row['id_0']] = $row['id_1'];
        }
        return $results;
    }

    /**
     * Unimport project content.
     *
     * @param ScriptoProject $project
     */
    public function unimportProject(ScriptoProject $project)
    {
        if (!$project->getProperty()) {
            throw new Exception\RuntimeException('Cannot unimport a project without a property.'); // @translate
        }

        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $itemIds = $this->getProjectItemIds($project);
        $mediaIds = $this->getProjectMediaIds($project);

        // Get resource IDs depending on the project's import target.
        switch ($project->getImportTarget()) {
            case 'item':
                $resourceIds = $itemIds;
                break;
            case 'media':
                $resourceIds = $mediaIds;
                break;
            default:
                $resourceIds = array_merge($itemIds, $mediaIds);
        }

        // Delete all the project's Scripto media HTML. Prevent slow execution
        // by deleting in chunks.
        $sMediaIds = array_keys($mediaIds);
        foreach (array_chunk($sMediaIds, 100) as $sMediaIdsChunk) {
            $qb = $em->createQueryBuilder();
            $qb->update('Scripto\Entity\ScriptoMedia', 'sm')
                ->set('sm.importedHtml', ':null')
                ->andWhere($qb->expr()->in('sm.id', $sMediaIdsChunk))
                ->setParameter('null', null)
                ->getQuery()->execute();
        }

        // Delete all resource values that match the project's property and
        // language. Prevent slow execution by deleting in chunks.
        foreach (array_chunk($resourceIds, 100) as $resourceIdsChunk) {
            $qb = $em->createQueryBuilder();
            $qb->delete('Omeka\Entity\Value', 'v')
                ->andWhere($qb->expr()->in('v.resource', $resourceIdsChunk))
                ->andWhere('v.property = :property_id')
                ->setParameter('property_id', $project->getProperty()->getId());
            if (null === $project->getLang()) {
                $qb->andWhere($qb->expr()->orX('v.lang IS NULL', "v.lang = ''"));
            } else {
                $qb->andWhere('v.lang = :lang')->setParameter('lang', $project->getLang());
            }
            $qb->getQuery()->execute();
        }

        // Set the project as not imported.
        $project->setImported(null);
        $em->flush();
    }
}
