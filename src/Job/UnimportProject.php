<?php
namespace Scripto\Job;

use DateTime;
use Omeka\Entity\Value;
use Scripto\Entity\ScriptoProject;
use Scripto\Mediawiki\Exception\ParseException;

/**
 * Unimport project content from Omeka items.
 */
class UnimportProject extends ScriptoJob
{
    public function perform()
    {
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $project = $em->find('Scripto\Entity\ScriptoProject', $this->getArg('scripto_project_id'));
        $this->unimportProject($project);
    }
}
