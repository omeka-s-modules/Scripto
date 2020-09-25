<?php
namespace Scripto\Controller\Admin;

use Omeka\Stdlib\Message;
use Scripto\Form\ProjectImportForm;
use Scripto\Form\ProjectUnimportForm;
use Scripto\Form\ProjectSyncForm;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class ItemController extends AbstractActionController
{
    public function browseAction()
    {
        $project = $this->scripto()->getRepresentation($this->params('project-id'));
        if (!$project) {
            return $this->redirect()->toRoute('admin/scripto');
        }

        $this->setBrowseDefaults('id');
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
        $view->setVariable('syncForm', $this->getForm(ProjectSyncForm::class, ['project' => $project]));
        $view->setVariable('importForm', $this->getForm(ProjectImportForm::class, ['project' => $project]));
        $view->setVariable('unimportForm', $this->getForm(ProjectUnimportForm::class, ['project' => $project]));
        $view->setVariable('sItems', $sItems);
        return $view;
    }

    public function showDetailsAction()
    {
        $sItem = $this->scripto()->getRepresentation(
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
