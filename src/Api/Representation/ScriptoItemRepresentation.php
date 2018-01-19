<?php
namespace Scripto\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class ScriptoItemRepresentation extends AbstractEntityRepresentation
{
    /**
     * Scripto item statuses
     */
    const STATUS_NEW = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_APPROVED = 3;

    public function getJsonLdType()
    {
        return 'o-module-scripto:Item';
    }

    public function getJsonLd()
    {
        return [
            'o-module-scripto:project' => $this->scriptoProject()->getReference(),
            'o:item' => $this->item()->getReference(),
            'o:created' => $this->getDateTime($this->created()),
            'o:modified' => $this->modified() ? $this->getDateTime($this->modified()) : null,
        ];
    }

    public function scriptoProject()
    {
        return $this->getAdapter('scripto_projects')
            ->getRepresentation($this->resource->getScriptoProject());
    }

    public function item()
    {
        return $this->getAdapter('items')
            ->getRepresentation($this->resource->getItem());
    }

    public function created()
    {
        return $this->resource->getCreated();
    }

    public function modified()
    {
        return $this->resource->getModified();
    }

    /**
     * Return the status of this item.
     *
     * The status is contingent on the status of child media.
     *
     * - APPROVED: implied by all Scripto media entities approved
     * - COMPLETED: implied by all Scripto media entities completed
     * - IN PROGRESS: implied by at least one Scripto media entity created
     * - NEW: implied by no Scripto media entities created
     *
     * Note the use of COUNT() queries instead of iterating every child media
     * resource. This is an optimization that reduces the queries needed to
     * determine status (requiring no more than four).
     *
     * @return int
     */
    public function status()
    {
        $services = $this->getServiceLocator();
        $em = $services->get('Omeka\EntityManager');

        $query = $em->createQuery('
            SELECT COUNT(m)
            FROM Scripto\Entity\ScriptoMedia m
            WHERE m.scriptoItem = :scripto_item_id'
        )->setParameter('scripto_item_id', $this->resource->getId());
        $totalScriptoMediaCount = $query->getSingleScalarResult();

        if (!$totalScriptoMediaCount) {
            return self::STATUS_NEW;
        }

        $query = $em->createQuery('
            SELECT COUNT(m)
            FROM Omeka\Entity\Media m
            WHERE m.item = :item_id'
        )->setParameter('item_id', $this->resource->getItem()->getId());
        $totalMediaCount = $query->getSingleScalarResult();

        $query = $em->createQuery('
            SELECT COUNT(m)
            FROM Scripto\Entity\ScriptoMedia m
            WHERE m.scriptoItem = :scripto_item_id
            AND m.isApproved = :is_approved'
        )->setParameters([
            'scripto_item_id' => $this->resource->getId(),
            'is_approved' => true,
        ]);
        $approvedCount = $query->getSingleScalarResult();
        if ($approvedCount === $totalMediaCount) {
            return self::STATUS_APPROVED;
        }

        $query = $em->createQuery('
            SELECT COUNT(m)
            FROM Scripto\Entity\ScriptoMedia m
            WHERE m.scriptoItem = :scripto_item_id
            AND m.isCompleted = :is_completed'
        )->setParameters([
            'scripto_item_id' => $this->resource->getId(),
            'is_completed' => true,
        ]);
        $completedCount = $query->getSingleScalarResult();
        if ($completedCount === $totalMediaCount) {
            return self::STATUS_COMPLETED;
        }

        return self::STATUS_IN_PROGRESS;
    }
}
