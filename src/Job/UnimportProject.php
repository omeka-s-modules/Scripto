<?php
namespace Scripto\Job;

use DateTime;
use Omeka\Entity\Value;
use Scripto\Entity\ScriptoProject;
use Scripto\Mediawiki\Exception\ParseException;

/**
 * Unimport text from Omeka items.
 */
class UnimportText extends ScriptoJob
{
    public function perform()
    {
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $project = $em->find('Scripto\Entity\ScriptoProject', $this->getArg('scripto_project_id'));
        $this->unimportText($project);
    }
}
