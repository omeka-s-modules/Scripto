<?php
namespace Scripto\Site\Navigation\Link;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\Navigation\Link\LinkInterface;
use Omeka\Stdlib\ErrorStore;

class Scripto implements LinkInterface
{
    public function getName()
    {
        return 'Scripto'; // @translate
    }

    public function getFormTemplate()
    {
        return 'common/navigation-link-form/scripto';
    }

    public function isValid(array $data, ErrorStore $errorStore)
    {
        if (!isset($data['label']) || '' === trim($data['label'])) {
            $errorStore->addError('o:navigation', 'Invalid navigation: Scripto link missing label');
            return false;
        }
        if (!isset($data['project_id']) || '' === trim($data['project_id'])) {
            $errorStore->addError('o:navigation', 'Invalid navigation: Scripto link missing project');
            return false;
        }
        return true;
    }

    public function getLabel(array $data, SiteRepresentation $site)
    {
        $label = null;
        if (isset($data['label']) && '' !== trim($data['label'])) {
            $label = $data['label'];
        }
        return $label;
    }

    public function toZend(array $data, SiteRepresentation $site)
    {
        return [
            'route' => 'scripto-project-id',
            'params' => [
                'site-slug' => $site->slug(),
                'site-project-id' => $data['project_id'],
                'project-id' => $data['project_id'],
            ],
        ];
    }

    public function toJstree(array $data, SiteRepresentation $site)
    {
        return [
            'label' => $data['label'],
            'project_id' => $data['project_id'],
        ];
    }
}
