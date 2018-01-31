<?php
namespace Scripto\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Exception;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class ScriptoItemAdapter extends AbstractEntityAdapter
{
    public function getResourceName()
    {
        return 'scripto_items';
    }

    public function getRepresentationClass()
    {
        return 'Scripto\Api\Representation\ScriptoItemRepresentation';
    }

    public function getEntityClass()
    {
        return 'Scripto\Entity\ScriptoItem';
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['scripto_project_id'])) {
            $alias = $this->createAlias();
            $qb->innerJoin('Scripto\Entity\ScriptoItem.scriptoProject', $alias);
            $qb->andWhere($qb->expr()->eq(
                "$alias.id",
                $this->createNamedParameter($qb, $query['scripto_project_id']))
            );
        }
        if (isset($query['item_id'])) {
            $alias = $this->createAlias();
            $qb->innerJoin('Scripto\Entity\ScriptoItem.item', $alias);
            $qb->andWhere($qb->expr()->eq(
                "$alias.id",
                $this->createNamedParameter($qb, $query['item_id']))
            );
        }
    }

    public function create(Request $request)
    {
        throw new Exception\OperationNotImplementedException(
            'The Scripto\Api\Adapter\ScriptoItemAdapter adapter does not implement the create operation.' // @translate
        );
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (null === $entity->getScriptoProject()) {
            $errorStore->addError('o-module-scripto:project', 'A Scripto project must not be null'); // @translate
        }
        if (null === $entity->getItem()) {
            $errorStore->addError('o:item', 'An item must not be null'); // @translate
        }
    }

    /**
     * Get total Scripto media count for a Scripto item.
     *
     * @param int $sItemId
     * @return int
     */
    public function getTotalScriptoMediaCount($sItemId)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT COUNT(m)
            FROM Scripto\Entity\ScriptoMedia m
            WHERE m.scriptoItem = :scripto_item_id'
        )->setParameter('scripto_item_id', $sItemId);
        return $query->getSingleScalarResult();
    }

    /**
     * Get approved Scripto media count for a Scripto item.
     *
     * @param int $sItemId
     * @return int
     */
    public function getApprovedScriptoMediaCount($sItemId)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT COUNT(m)
            FROM Scripto\Entity\ScriptoMedia m
            WHERE m.scriptoItem = :scripto_item_id
            AND m.isApproved = :is_approved'
        )->setParameters([
            'scripto_item_id' => $sItemId,
            'is_approved' => true,
        ]);
        return $query->getSingleScalarResult();
    }

    /**
     * Get completed Scripto media count for a Scripto item.
     *
     * @param int $sItemId
     * @return int
     */
    public function getCompletedScriptoMediaCount($sItemId)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT COUNT(m)
            FROM Scripto\Entity\ScriptoMedia m
            WHERE m.scriptoItem = :scripto_item_id
            AND m.isCompleted = :is_completed'
        )->setParameters([
            'scripto_item_id' => $sItemId,
            'is_completed' => true,
        ]);
        return $query->getSingleScalarResult();
    }

    /**
     * Get edited Scripto media count for a Scripto item.
     *
     * @param int $sItemId
     * @return int
     */
    public function getEditedScriptoMediaCount($sItemId)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT COUNT(m)
            FROM Scripto\Entity\ScriptoMedia m
            WHERE m.scriptoItem = :scripto_item_id
            AND m.edited IS NOT NULL'
        )->setParameter('scripto_item_id', $sItemId);
        return $query->getSingleScalarResult();
    }
}
