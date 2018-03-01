<?php
namespace Scripto\Controller\Admin;

use Zend\View\Model\ViewModel;
use Scripto\Form\ImportProjectForm;
use Scripto\Form\SyncProjectForm;
use Scripto\Form\UnimportProjectForm;

class ItemController extends AbstractScriptoController
{
    public function browseAction()
    {
        $project = $this->getScriptoRepresentation($this->params('project-id'));

        $this->setBrowseDefaults('synced');
        $query = array_merge(
            ['scripto_project_id' => $this->params('project-id')],
            $this->params()->fromQuery()
        );
        $response = $this->api()->search('scripto_items', $query);
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $sItems = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('project', $project);
        $view->setVariable('syncForm', $this->getForm(SyncProjectForm::class));
        $view->setVariable('importForm', $this->getForm(ImportProjectForm::class));
        $view->setVariable('unimportForm', $this->getForm(UnimportProjectForm::class));
        $view->setVariable('sItems', $sItems);
        return $view;
    }

    public function showDetailsAction()
    {
        $sItem = $this->getScriptoRepresentation(
            $this->params('project-id'),
            $this->params('item-id')
        );
        if (!$sItem) {
            exit;
        }

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('sItem', $sItem);
        $view->setVariable('project', $sItem->scriptoProject());
        $view->setVariable('item', $sItem->item());
        return $view;
    }

    public function showAction()
    {
        return $this->redirect()->toRoute('admin/scripto-media', ['action' => 'browse'], true);
    }
}
