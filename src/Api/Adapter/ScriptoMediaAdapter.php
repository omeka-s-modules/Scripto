<?php
namespace Scripto\Api\Adapter;

use DateTime;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Exception;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

/**
 * Scripto media adapter
 */
class ScriptoMediaAdapter extends AbstractEntityAdapter
{
    protected $sortFields = [
        'position' => 'position',
        'synced' => 'synced',
        'edited' => 'edited',
        'completed' => 'completed',
        'approved' => 'approved',
    ];

    public function getResourceName()
    {
        return 'scripto_media';
    }

    public function getRepresentationClass()
    {
        return 'Scripto\Api\Representation\ScriptoMediaRepresentation';
    }

    public function getEntityClass()
    {
        return 'Scripto\Entity\ScriptoMedia';
    }

    public function create(Request $request)
    {
        // Scripto items are created only when a project is synced.
        throw new Exception\OperationNotImplementedException(
            'The Scripto\Api\Adapter\ScriptoMediaAdapter adapter does not implement the create operation.' // @translate
        );
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['scripto_item_id'])) {
            $alias = $this->createAlias();
            $qb->innerJoin('Scripto\Entity\ScriptoMedia.scriptoItem', $alias);
            $qb->andWhere($qb->expr()->eq(
                "$alias.id",
                $this->createNamedParameter($qb, $query['scripto_item_id']))
            );
        }
        if (isset($query['media_id'])) {
            $alias = $this->createAlias();
            $qb->innerJoin('Scripto\Entity\ScriptoMedia.media', $alias);
            $qb->andWhere($qb->expr()->eq(
                "$alias.id",
                $this->createNamedParameter($qb, $query['media_id']))
            );
        }
        if (isset($query['is_approved'])) {
            $qb->andWhere($qb->expr()->isNotNull('Scripto\Entity\ScriptoMedia.approved'));
        } elseif (isset($query['is_not_approved'])) {
            $qb->andWhere($qb->expr()->isNull('Scripto\Entity\ScriptoMedia.approved'));
        }
        if (isset($query['is_completed'])) {
            $qb->andWhere($qb->expr()->isNotNull('Scripto\Entity\ScriptoMedia.completed'));
        } elseif (isset($query['is_not_completed'])) {
            $qb->andWhere($qb->expr()->isNull('Scripto\Entity\ScriptoMedia.completed'));
        }
        if (isset($query['is_edited'])) {
            $qb->andWhere($qb->expr()->isNotNull('Scripto\Entity\ScriptoMedia.edited'));
        } elseif (isset($query['is_not_edited'])) {
            $qb->andWhere($qb->expr()->isNull('Scripto\Entity\ScriptoMedia.edited'));
        }
        if (isset($query['is_edited_after_approved'])) {
            $qb->andWhere($qb->expr()->gt('Scripto\Entity\ScriptoMedia.edited', 'Scripto\Entity\ScriptoMedia.approved'));
        }
        if (isset($query['is_edited_after_imported'])) {
            $aliasItem = $this->createAlias();
            $qb->innerJoin('Scripto\Entity\ScriptoMedia.scriptoItem', 'Scripto\Entity\ScriptoItem');
            $aliasProject = $this->createAlias();
            $qb->innerJoin('Scripto\Entity\ScriptoItem.scriptoProject', $aliasProject);
            $qb->andWhere($qb->expr()->gt('Scripto\Entity\ScriptoMedia.edited', "$aliasProject.imported"));
        }
        if (isset($query['is_synced_after_imported'])) {
            $aliasItem = $this->createAlias();
            $qb->innerJoin('Scripto\Entity\ScriptoMedia.scriptoItem', 'Scripto\Entity\ScriptoItem');
            $aliasProject = $this->createAlias();
            $qb->innerJoin('Scripto\Entity\ScriptoItem.scriptoProject', $aliasProject);
            $qb->andWhere($qb->expr()->gt('Scripto\Entity\ScriptoMedia.synced', "$aliasProject.imported"));
        }
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        $mwUser = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient')->getUserInfo();
        $oUser = $this->getServiceLocator()->get('Omeka\AuthenticationService')->getIdentity();
        $setIsCompleted = $request->getValue('o-module-scripto:is_completed');
        $setIsApproved = $request->getValue('o-module-scripto:is_approved');

        if (null !== $setIsCompleted) {
            if ($setIsCompleted && !$entity->getCompleted()) {
                // Set as completed only if the entity is set as not completed.
                $entity->setCompleted(new DateTime('now'));
                $entity->setCompletedBy($mwUser['name']);
            } elseif (!$setIsCompleted && $entity->getCompleted()) {
                // Set as not completed only if the entity is set as completed.
                $entity->setCompleted(null);
                $entity->setCompletedBy($mwUser['name']);
            }
        }
        if (null !== $setIsApproved) {
            if ($setIsApproved && !$entity->getApproved()) {
                // Set as approved only if the entity is set as not approved.
                $entity->setApproved(new DateTime('now'));
                $entity->setApprovedBy($oUser);
            } elseif (!$setIsApproved && $entity->getApproved()) {
                // Set as not approved only if the entity is set as approved.
                $entity->setApproved(null);
                $entity->setApprovedBy($oUser);
            }
        }

        $entity->setText($request->getValue('o-module-scripto:text'));
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (null === $entity->getScriptoItem()) {
            $errorStore->addError('o-module-scripto:item', 'A Scripto item must not be null.'); // @translate
        }
        if (null === $entity->getMedia()) {
            $errorStore->addError('o:media', 'A media must not be null.'); // @translate
        }
    }
}
