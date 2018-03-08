<?php
namespace Scripto\Controller\Admin;

use Omeka\Stdlib\Message;
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

        if (!$project->itemSet()) {
            $message = new Message(
                'This project has no item set. %s', // @translate
                sprintf(
                    '<a href="%s">%s</a>',
                    htmlspecialchars($project->adminUrl('edit')),
                    $this->translate('Set an item set here.')
                ));
            $message->setEscapeHtml(false);
            $this->messenger()->addError($message);
        }
        if (!$project->property()) {
            $message = new Message(
                'This project has no property. %s', // @translate
                sprintf(
                    '<a href="%s">%s</a>',
                    htmlspecialchars($project->adminUrl('edit')),
                    $this->translate('Set a property here.')
                ));
            $message->setEscapeHtml(false);
            $this->messenger()->addError($message);
        }

        $view = new ViewModel;
        $view->setVariable('project', $project);
        $view->setVariable('syncForm', $this->getForm(SyncProjectForm::class, ['project' => $project]));
        $view->setVariable('importForm', $this->getForm(ImportProjectForm::class, ['project' => $project]));
        $view->setVariable('unimportForm', $this->getForm(UnimportProjectForm::class, ['project' => $project]));
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
