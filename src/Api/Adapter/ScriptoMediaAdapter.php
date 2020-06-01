<?php
namespace Scripto\Api\Adapter;

use DateTime;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Exception;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Scripto\Entity\ScriptoMedia;
use Scripto\Mediawiki\Exception as MediaWikiException;

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
        return \Scripto\Api\Representation\ScriptoMediaRepresentation::class;
    }

    public function getEntityClass()
    {
        return \Scripto\Entity\ScriptoMedia::class;
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
            $qb->innerJoin('omeka_root.scriptoItem', $alias);
            $qb->andWhere($qb->expr()->eq(
                "$alias.id",
                $this->createNamedParameter($qb, $query['scripto_item_id']))
            );
        }
        if (isset($query['media_id'])) {
            $alias = $this->createAlias();
            $qb->innerJoin('omeka_root.media', $alias);
            $qb->andWhere($qb->expr()->eq(
                "$alias.id",
                $this->createNamedParameter($qb, $query['media_id']))
            );
        }
        if (isset($query['is_approved'])) {
            $qb->andWhere($qb->expr()->isNotNull('omeka_root.approved'));
        } elseif (isset($query['is_not_approved'])) {
            $qb->andWhere($qb->expr()->isNull('omeka_root.approved'));
        }
        if (isset($query['is_completed'])) {
            $qb->andWhere($qb->expr()->isNotNull('omeka_root.completed'));
        } elseif (isset($query['is_not_completed'])) {
            $qb->andWhere($qb->expr()->isNull('omeka_root.completed'));
        }
        if (isset($query['is_edited'])) {
            $qb->andWhere($qb->expr()->isNotNull('omeka_root.edited'));
        } elseif (isset($query['is_not_edited'])) {
            $qb->andWhere($qb->expr()->isNull('omeka_root.edited'));
        }
        if (isset($query['is_edited_after_approved'])) {
            $qb->andWhere($qb->expr()->gt('omeka_root.edited', 'omeka_root.approved'));
        }
        if (isset($query['is_edited_after_imported'])) {
            $aliasItem = $this->createAlias();
            $qb->innerJoin('omeka_root.scriptoItem', $aliasItem);
            $aliasProject = $this->createAlias();
            $qb->innerJoin("$aliasItem.scriptoProject", $aliasProject);
            $qb->andWhere($qb->expr()->gt('omeka_root.edited', "$aliasProject.imported"));
        }
        if (isset($query['is_synced_after_imported'])) {
            $aliasItem = $this->createAlias();
            $qb->innerJoin('omeka_root.scriptoItem', $aliasItem);
            $aliasProject = $this->createAlias();
            $qb->innerJoin("$aliasItem.scriptoProject", $aliasProject);
            $qb->andWhere($qb->expr()->gt('omeka_root.synced', "$aliasProject.imported"));
        }
        if (isset($query['has_imported_html'])) {
            $qb->andWhere($qb->expr()->isNotNull('omeka_root.importedHtml'));
        }
        if (isset($query['search'])) {
            // Filter by search query. Equivalent to property=null, type=in.
            $value = $query['search'];
            $mediaAlias = $this->createAlias();
            $valueAlias = $this->createAlias();
            $param = $this->createNamedParameter($qb, "%$value%");
            $qb->leftJoin('omeka_root.media', $mediaAlias)
                ->leftJoin("$mediaAlias.values", $valueAlias)
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
        $services = $this->getServiceLocator();
        $mwUser = $services->get('Scripto\Mediawiki\ApiClient')->queryUserInfo();
        $oUser = $services->get('Omeka\AuthenticationService')->getIdentity();

        $setIsCompleted = $request->getValue('o-module-scripto:is_completed');
        $setIsApproved = $request->getValue('o-module-scripto:is_approved');
        $wikitext = $request->getValue('o-module-scripto:wikitext');
        $summary = $request->getValue('o-module-scripto:summary');

        if (null !== $setIsCompleted) {
            // Anyone can set completed.
            if ($setIsCompleted) {
                $entity->setCompleted(new DateTime('now'));
                $entity->setCompletedBy($mwUser['name']);
                $entity->setCompletedRevision($request->getValue('o-module-scripto:completed_revision'));
            } else {
                $entity->setCompleted(null);
                $entity->setCompletedBy(null);
                $entity->setCompletedRevision(null);
            }
        }
        if (null !== $setIsApproved) {
            // The user must have Scripto review rights to set approved.
            $this->authorize($entity, 'review');
            if ($setIsApproved) {
                $entity->setApproved(new DateTime('now'));
                $entity->setApprovedBy($oUser);
                $entity->setApprovedRevision($request->getValue('o-module-scripto:approved_revision'));
            } else {
                $entity->setApproved(null);
                $entity->setApprovedBy(null);
                $entity->setApprovedRevision(null);
            }
        }

        if (is_string($wikitext)) {
            // The user must have MediaWiki createpage/edit rights to set
            // wikitext. This is checked during api.hydrate.post.
            $entity->setWikitextData($wikitext, $summary, $setIsCompleted, $setIsApproved);
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        if (null === $entity->getScriptoItem()) {
            $errorStore->addError('o-module-scripto:item', 'A Scripto item must not be null.'); // @translate
        }
        if (null === $entity->getMedia()) {
            $errorStore->addError('o:media', 'A media must not be null.'); // @translate
        }
        if (null !== $entity->getCompletedRevision()) {
            try {
                $client->queryRevision($entity->getMediawikiPageTitle(), $entity->getCompletedRevision());
            } catch (MediaWikiException\QueryException $e) {
                $errorStore->addError('o-module-scripto:completed_revision', 'Invalid completed revision ID.'); // @translate
            }
        }
        if (null !== $entity->getApprovedRevision()) {
            try {
                $client->queryRevision($entity->getMediawikiPageTitle(), $entity->getApprovedRevision());
            } catch (MediaWikiException\QueryException $e) {
                $errorStore->addError('o-module-scripto:approved_revision', 'Invalid approved revision ID.'); // @translate
            }
        }
    }

    public function preprocessBatchUpdate(array $data, Request $request)
    {
        $rawData = $request->getContent();
        if (isset($rawData['o-module-scripto:is_approved'])) {
            $data['o-module-scripto:is_approved'] = $rawData['o-module-scripto:is_approved'];
        }
        if (isset($rawData['o-module-scripto:is_completed'])) {
            $data['o-module-scripto:is_completed'] = $rawData['o-module-scripto:is_completed'];
        }
        return $data;
    }

    /**
     * Get the previous Scripto media.
     *
     * @param ScriptoMedia
     * @return \Scripto\Api\Representation\ScriptoMediaRepresentation|null
     */
    public function getPreviousScriptoMedia(ScriptoMedia $sMedia)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT sm
            FROM Scripto\Entity\ScriptoMedia sm
            JOIN sm.scriptoItem si
            WHERE si = :scripto_item
            AND sm.position < :position
            ORDER BY sm.position DESC
        ')->setParameters([
            'scripto_item' => $sMedia->getScriptoItem(),
            'position' => $sMedia->getPosition(),
        ])->setMaxResults(1);
        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Get the next Scripto media.
     *
     * @param ScriptoMedia
     * @return \Scripto\Api\Representation\ScriptoMediaRepresentation|null
     */
    public function getNextScriptoMedia(ScriptoMedia $sMedia)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT sm
            FROM Scripto\Entity\ScriptoMedia sm
            JOIN sm.scriptoItem si
            WHERE si = :scripto_item
            AND sm.position > :position
            ORDER BY sm.position ASC
        ')->setParameters([
            'scripto_item' => $sMedia->getScriptoItem(),
            'position' => $sMedia->getPosition(),
        ])->setMaxResults(1);
        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}
