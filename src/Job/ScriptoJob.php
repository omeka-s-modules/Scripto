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
        )->setParameter('scripto_project_id', $project->getId());
        return array_column($query->getResult(), 'item_id', 'scripto_item_id');
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
        )->setParameter('scripto_project_id', $project->getId());
        return array_column($query->getResult(), 'media_id', 'scripto_media_id');
    }

    /**
     * Unimport project content.
     *
     * Deletes all content that was previously imported to the items in the Scripto
     * project matching the project's property and language.
     *
     * @param ScriptoProject $project
     */
    public function unimportProject(ScriptoProject $project)
    {
        if (!$project->getProperty()) {
            throw new Exception\RuntimeException('Cannot unimport a project without a property.');
        }

        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $itemIds = $this->getProjectItemIds($project);

        // Get resource IDs depending on the project's import target.
        if ('item' === $project->getImportTarget()) {
            $resourceIds = $this->getProjectItemIds($project);
        } elseif ('media' === $project->getImportTarget()) {
            $resourceIds = $this->getProjectMediaIds($project);
        } else {
            $resourceIds = array_merge(
                $this->getProjectItemIds($project),
                $this->getProjectMediaIds($project)
            );
        }

        // Prevent slow execution by deleting in chunks.
        foreach (array_chunk($resourceIds, 100, true) as $resourceIdsChunk) {
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
