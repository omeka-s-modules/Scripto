<?php
namespace Scripto\Api\Adapter;

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
        'id' => 'id',
        'position' => 'position',
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
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        $mwUser = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient')->getUserInfo();
        $oUser = $this->getServiceLocator()->get('Omeka\AuthenticationService')->getIdentity();
        $isCompleted = $request->getValue('o-module-scripto:is_completed');
        $isApproved = $request->getValue('o-module-scripto:is_approved');

        if (null !== $isCompleted) {
            if ($isCompleted && !$entity->getIsCompleted()) {
                // Set as completed only if the entity is set as not completed.
                $entity->setIsCompleted(true);
                $entity->setCompletedBy($mwUser['name']);
            } elseif (!$isCompleted && $entity->getIsCompleted()) {
                // Set as not completed only if the entity is set as completed.
                $entity->setIsCompleted(false);
                $entity->setCompletedBy($mwUser['name']);
            }
        }
        if (null !== $isApproved) {
            if ($isApproved && !$entity->getIsApproved()) {
                // Set as approved only if the entity is set as not approved.
                $entity->setIsApproved(true);
                $entity->setApprovedBy($oUser);
            } elseif (!$isApproved && $entity->getIsApproved()) {
                // Set as not approved only if the entity is set as approved.
                $entity->setIsApproved(false);
                $entity->setApprovedBy($oUser);
            }
        }
        if ($entity->getIsApproved() && !$entity->getIsCompleted()) {
            // Automatically set as completed if set as approved.
            $entity->setIsCompleted(true);
            $entity->setCompletedBy($mwUser['name']);
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
