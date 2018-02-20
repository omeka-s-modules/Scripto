<?php
namespace Scripto\Job;

use DateTime;
use Omeka\Entity\Value;
use Scripto\Entity\ScriptoProject;

/**
 * Import project text from MediaWiki to Omeka items.
 */
class ImportProject extends ScriptoJob
{
    public function perform()
    {
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $project = $em->find('Scripto\Entity\ScriptoProject', $this->getArg('scripto_project_id'));
        $this->importText($project);
    }

    /**
     * Import text from MediaWiki to Omeka items.
     *
     * @param ScriptoProject $project
     */
    public function importText(ScriptoProject $project)
    {
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');

        // First, unimport all project texts.
        $this->unimportText($project);

        // Iterate all project items.
        $itemIds = $this->getProjectItemIds($project);
        foreach (array_chunk($itemIds, 50, true) as $itemIdsChunk) {
            foreach ($itemIdsChunk as $sItemId => $itemId) {
                $sItem = $em->getReference('Scripto\Entity\ScriptoItem', $sItemId);

                // Iterate all item media.
                $mediaText = [];
                foreach ($sItem->getScriptoMedia() as $sMedia) {
                    // Only import text if the media has been approved.
                    if ($sMedia->getApproved()) {
                        $mediaText[] = $client->parsePage($sMedia->getMediawikiPageTitle());
                    }
                }
                if ($mediaText) {
                    // Remove uncreated pages and strip HTML from text.
                    $itemText = strip_tags(implode(' ', array_filter($mediaText)));

                    // Build a new value.
                    $value = new Value;
                    $value->setResource($sItem->getItem());
                    $value->setProperty($project->getProperty());
                    $value->setType('literal');
                    $value->setValue($itemText);
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
