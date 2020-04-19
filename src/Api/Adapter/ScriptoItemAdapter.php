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
    protected $sortFields = [
        'synced' => 'synced',
        'edited' => 'edited',
    ];

    public function getResourceName()
    {
        return 'scripto_items';
    }

    public function getRepresentationClass()
    {
        return \Scripto\Api\Representation\ScriptoItemRepresentation::class;
    }

    public function getEntityClass()
    {
        return \Scripto\Entity\ScriptoItem::class;
    }

    public function create(Request $request)
    {
        // Scripto items are created only when a project is synced.
        throw new Exception\OperationNotImplementedException(
            'The Scripto\Api\Adapter\ScriptoItemAdapter adapter does not implement the create operation.' // @translate
        );
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['scripto_project_id'])) {
            $alias = $this->createAlias();
            $qb->innerJoin('omeka_root.scriptoProject', $alias);
            $qb->andWhere($qb->expr()->eq(
                "$alias.id",
                $this->createNamedParameter($qb, $query['scripto_project_id']))
            );
        }
        if (isset($query['item_id'])) {
            $alias = $this->createAlias();
            $qb->innerJoin('omeka_root.item', $alias);
            $qb->andWhere($qb->expr()->eq(
                "$alias.id",
                $this->createNamedParameter($qb, $query['item_id']))
            );
        }
        if (isset($query['is_approved'])) {
            // Get all approved Scripto items. An item is "approved" if a) all
            // child media are marked as approved, or b) it has no child media.
            $alias = $this->createAlias();
            $subQb = $this->getEntityManager()->createQueryBuilder()
                ->select($alias)
                ->from('Scripto\Entity\ScriptoMedia', $alias)
                ->andWhere("$alias.scriptoItem = omeka_root.id")
                ->andWhere("$alias.approved IS NULL");
            $qb->andWhere($qb->expr()->not($qb->expr()->exists($subQb->getDQL())));
        } elseif (isset($query['is_not_approved']) || isset($query['is_in_progress']) || isset($query['is_new'])) {
            // Get all not approved Scripto items. An item is "not approved" if
            // at least one child media is not marked as approved.
            $alias = $this->createAlias();
            $subQb = $this->getEntityManager()->createQueryBuilder()
                ->select($alias)
                ->from('Scripto\Entity\ScriptoMedia', $alias)
                ->andWhere("$alias.scriptoItem = omeka_root.id")
                ->andWhere("$alias.approved IS NULL");
            $qb->andWhere($qb->expr()->exists($subQb->getDQL()));
        }

        if (isset($query['is_in_progress'])) {
            // Get all in progress Scripto items. An item is "in progress" if
            // a) at least one child media is not marked as approved, and b)
            // at least one child media has been edited. Note that an "in
            // progress" item can be marked as "In progress" and "Completed".
            $qb->andWhere($qb->expr()->isNotNull('omeka_root.edited'));
        } elseif (isset($query['is_new'])) {
            // Get all new Scripto items. An item is "new" if a) at least one
            // child media is not marked as approved, and b) no child media has
            // been edited. Note that media can be not edited but marked as
            // completed, so a "new" item can be marked as "New" and "Completed".
            $qb->andWhere($qb->expr()->isNull('omeka_root.edited'));
        }

        if (isset($query['is_edited_after_imported'])) {
            $alias = $this->createAlias();
            $qb->innerJoin('omeka_root.scriptoProject', $alias);
            $qb->andWhere($qb->expr()->gt('omeka_root.edited', "$alias.imported"));
        }

        if (isset($query['search'])) {
            // Filter by search query. Equivalent to property=null, type=in.
            $value = $query['search'];
            $itemAlias = $this->createAlias();
            $valueAlias = $this->createAlias();
            $param = $this->createNamedParameter($qb, "%$value%");
            $qb->leftJoin('omeka_root.item', $itemAlias)
                ->leftJoin("$itemAlias.values", $valueAlias)
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->like("$valueAlias.value", $param),
                    $qb->expr()->like("$valueAlias.uri", $param)
                ));
        }
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
}
