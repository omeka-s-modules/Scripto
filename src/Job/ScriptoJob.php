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
     * Unimport project text.
     *
     * Deletes all text that was previously imported to the items in the Scripto
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
        $qb = $em->createQueryBuilder();
        $qb->delete('Omeka\Entity\Value', 'v')
            ->andWhere($qb->expr()->in('v.resource', $itemIds))
            ->andWhere('v.property = :property_id')
            ->setParameter('property_id', $project->getProperty()->getId());
        if (null === $project->getLang()) {
            $qb->andWhere('v.lang IS NULL');
        } else {
            $qb->andWhere('v.lang = :lang')->setParameter('lang', $project->getLang());
        }
        $qb->getQuery()->execute();

        // Set the project as not imported.
        $project->setImported(null);
        $em->flush();

    }
}
