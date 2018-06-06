<?php
namespace Scripto\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Omeka\Api\Exception\NotFoundException;
use Zend\Form\Element\Select;
use Zend\View\Renderer\PhpRenderer;

class Scripto extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Scripto'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {}

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null)
    {
        $options = [];
        $projects = $view->api()->search('scripto_projects')->getContent();
        foreach ($projects as $project) {
            $options[$project->id()] = $project->title();
        }
        $select = new Select('o:block[__blockIndex__][o:data][project]');
        $select->setEmptyOption($view->translate('Select one'));
        $select->setValueOptions($options);
        if ($block) {
            $select->setValue($block->dataValue('project'));
        }
        return $view->partial('common/block-layout/scripto-block-form', [
            'select' => $select,
        ]);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $projectId = $block->dataValue('project');
        try {
            $project = $view->api()->read('scripto_projects', $projectId)->getContent();
        } catch (NotFoundException $e) {
            $project = null;
        }
        return $view->partial('common/block-layout/scripto-block', [
            'project' => $project,
        ]);
    }
}
