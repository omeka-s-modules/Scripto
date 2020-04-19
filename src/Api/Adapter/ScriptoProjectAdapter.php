<?php
namespace Scripto\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Scripto\Entity\ScriptoReviewer;

class ScriptoProjectAdapter extends AbstractEntityAdapter
{
    protected $sortFields = [
        'title' => 'title',
        'created' => 'created',
        'synced' => 'synced',
        'imported' => 'imported',
    ];

    public function getResourceName()
    {
        return 'scripto_projects';
    }

    public function getRepresentationClass()
    {
        return \Scripto\Api\Representation\ScriptoProjectRepresentation::class;
    }

    public function getEntityClass()
    {
        return \Scripto\Entity\ScriptoProject::class;
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['owner_id'])) {
            $alias = $this->createAlias();
            $qb->innerJoin('omeka_root.owner', $alias);
            $qb->andWhere($qb->expr()->eq(
                "$alias.id",
                $this->createNamedParameter($qb, $query['owner_id']))
            );
        }
        if (isset($query['item_set_id'])) {
            $alias = $this->createAlias();
            $qb->innerJoin('omeka_root.itemSet', $alias);
            $qb->andWhere($qb->expr()->eq(
                "$alias.id",
                $this->createNamedParameter($qb, $query['item_set_id']))
            );
        }
        if (isset($query['property_id'])) {
            $alias = $this->createAlias();
            $qb->innerJoin('omeka_root.property', $alias);
            $qb->andWhere($qb->expr()->eq(
                "$alias.id",
                $this->createNamedParameter($qb, $query['property_id']))
            );
        }
        if (isset($query['has_reviewer_id'])) {
            $alias = $this->createAlias();
            $qb->innerJoin('omeka_root.reviewers', $alias);
            $qb->andWhere($qb->expr()->eq(
                "$alias.user",
                $this->createNamedParameter($qb, $query['has_reviewer_id']))
            );
        }
    }
    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (isset($data['o:owner']) && !isset($data['o:owner']['o:id'])) {
            $errorStore->addError('o:owner', 'An owner must have an ID'); // @translate
        }
        if (isset($data['o:item_set']) && !isset($data['o:item_set']['o:id'])) {
            $errorStore->addError('o:item_set', 'An item set must have an ID'); // @translate
        }
        if (isset($data['o:property']) && !isset($data['o:property']['o:id'])) {
            $errorStore->addError('o:property', 'A property must have an ID'); // @translate
        }
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        $this->hydrateOwner($request, $entity);
        if ($this->shouldHydrate($request, 'o:is_public')) {
            $entity->setIsPublic($request->getValue('o:is_public', true));
        }
        if ($this->shouldHydrate($request, 'o:item_set')) {
            $itemSet = $request->getValue('o:item_set');
            if ($itemSet) {
                $itemSet = $this->getAdapter('item_sets')->findEntity($itemSet['o:id']);
            }
            $entity->setItemSet($itemSet);
        }
        if ($this->shouldHydrate($request, 'o-module-scripto:media_types')) {
            $entity->setMediaTypes($request->getValue('o-module-scripto:media_types'));
        }
        if ($this->shouldHydrate($request, 'o:property')) {
            $property = $request->getValue('o:property');
            if ($property) {
                $property = $this->getAdapter('properties')->findEntity($property['o:id']);
            }
            $entity->setProperty($property);
        }
        if ($this->shouldHydrate($request, 'o:lang')) {
            $entity->setLang($request->getValue('o:lang'));
        }
        if ($this->shouldHydrate($request, 'o-module-scripto:title')) {
            $entity->setTitle($request->getValue('o-module-scripto:title'));
        }
        if ($this->shouldHydrate($request, 'o-module-scripto:guidelines')) {
            $entity->setGuidelines($request->getValue('o-module-scripto:guidelines'));
        }
        if ($this->shouldHydrate($request, 'o-module-scripto:create_account_text')) {
            $entity->setCreateAccountText($request->getValue('o-module-scripto:create_account_text'));
        }
        if ($this->shouldHydrate($request, 'o-module-scripto:description')) {
            $entity->setDescription($request->getValue('o-module-scripto:description'));
        }
        if ($this->shouldHydrate($request, 'o-module-scripto:import_target')) {
            $entity->setImportTarget($request->getValue('o-module-scripto:import_target'));
        }
        if ($this->shouldHydrate($request, 'o-module-scripto:browse_layout')) {
            $entity->setBrowseLayout($request->getValue('o-module-scripto:browse_layout'));
        }
        if ($this->shouldHydrate($request, 'o-module-scripto:filter_approved')) {
            $entity->setFilterApproved($request->getValue('o-module-scripto:filter_approved'));
        }
        if ($this->shouldHydrate($request, 'o-module-scripto:item_type')) {
            $entity->setItemType($request->getValue('o-module-scripto:item_type'));
        }
        if ($this->shouldHydrate($request, 'o-module-scripto:media_type')) {
            $entity->setMediaType($request->getValue('o-module-scripto:media_type'));
        }
        if ($this->shouldHydrate($request, 'o-module-scripto:content_type')) {
            $entity->setContentType($request->getValue('o-module-scripto:content_type'));
        }
        if ($this->shouldHydrate($request, 'o-module-scripto:reviewer')) {
            $userAdapter = $this->getAdapter('users');
            $reviewers = $entity->getReviewers();
            $reviewersNew = $request->getValue('o-module-scripto:reviewer');
            $reviewersNew = is_array($reviewersNew) ? $reviewersNew : [];

            // Add reviewers to the project.
            $reviewersToRetain = [];
            foreach ($reviewersNew as $reviewerNew) {
                if (!isset($reviewerNew['o:user']['o:id'])) {
                    continue;
                }

                $user = $userAdapter->findEntity($reviewerNew['o:user']['o:id']);
                // The $reviewers collection is indexed by user_id.
                $reviewer = $reviewers->get($user->getId());
                if (!$reviewer) {
                    $reviewer = new ScriptoReviewer;
                    $reviewer->setUser($user);
                    $reviewer->setScriptoProject($entity);
                    $reviewers->set($user->getId(), $reviewer);
                }
                $reviewersToRetain[] = $reviewer;
            }

            // Remove reviewers from the project.
            foreach ($reviewers as $reviewer) {
                if (!in_array($reviewer, $reviewersToRetain)) {
                    $reviewers->removeElement($reviewer);
                }
            }
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (null === $entity->getTitle()) {
            $errorStore->addError('o-module-scripto:title', 'A Scripto project title must not be null'); // @translate
        }
    }
}
