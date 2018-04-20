<?php
namespace Scripto\Job;

use DateTime;
use Omeka\Entity\Value;
use Omeka\Job\Exception;
use Scripto\Entity\ScriptoProject;

/**
 * Import project content from MediaWiki to Omeka items.
 */
class ImportProject extends ScriptoJob
{
    public function perform()
    {
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $project = $em->find('Scripto\Entity\ScriptoProject', $this->getArg('scripto_project_id'));
        $this->importProject($project);
    }

    /**
     * Import project content from MediaWiki to Omeka items.
     *
     * @param ScriptoProject $project
     */
    public function importProject(ScriptoProject $project)
    {
        if (!$project->getProperty()) {
            throw new Exception\RuntimeException('Cannot import a project without a property.');
        }

        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');

        // First, unimport all project contents.
        $this->unimportProject($project);

        // Iterate all project items.
        $itemIds = $this->getProjectItemIds($project);
        foreach (array_chunk($itemIds, 50, true) as $itemIdsChunk) {

            // Must re-get the Property entity after calling clear() because,
            // otherwise, the entity manager will see $project->getProperty() as
            // NULL when hydrating Value. This may be caused by some unusual
            // treatment of Doctrine proxies during flush().
            $property = $em->getReference('Omeka\Entity\Property', $project->getProperty()->getId());

            foreach ($itemIdsChunk as $sItemId => $itemId) {
                $sItem = $em->getReference('Scripto\Entity\ScriptoItem', $sItemId);

                // Iterate all item media.
                $mediaContent = [];
                foreach ($sItem->getScriptoMedia() as $sMedia) {
                    // Only import content if the media has been approved.
                    if ($sMedia->getApproved()) {
                        $mediaContent[] = $sMedia->getApprovedRevision()
                            // Get content from the specified revision.
                            ? $client->parseRevision($sMedia->getApprovedRevision())
                            // Get content from the latest revision.
                            : $client->parsePage($sMedia->getMediawikiPageTitle());
                    }
                }
                if ($mediaContent) {
                    // Remove uncreated pages and strip HTML from content.
                    $itemContent = strip_tags(implode(' ', array_filter($mediaContent)));

                    // Build a new value.
                    $value = new Value;
                    $value->setResource($sItem->getItem());
                    $value->setProperty($property);
                    $value->setType('literal');
                    $value->setValue($itemContent);
                    $value->setLang($project->getLang());

                    $em->persist($value);
                }
            }
            $em->flush();
            $em->clear();
        }

        $project->setImported(new DateTime('now'));
        $em->merge($project); // entity is detached because of clear()
        $em->flush();
    }
}
