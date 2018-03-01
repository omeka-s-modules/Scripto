<?php
namespace Scripto\Controller\Admin;

use Scripto\Form\BatchReviewMediaForm;
use Zend\View\Model\ViewModel;

class MediaController extends AbstractScriptoController
{
    public function browseAction()
    {
        $sItem = $this->getScriptoRepresentation(
            $this->params('project-id'),
            $this->params('item-id')
        );

        $this->setBrowseDefaults('position', 'asc');
        $query = array_merge(
            ['scripto_item_id' => $sItem->id()],
            $this->params()->fromQuery()
        );
        $response = $this->api()->search('scripto_media', $query);
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $sMedia = $response->getContent();

        $batchForm = $this->getForm(BatchReviewMediaForm::class, [
            'formaction' => (string) $this->url()->fromRoute(null, ['action' => 'batch-review'], true),
        ]);
        $view = new ViewModel;
        $view->setVariable('sItem', $sItem);
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('project', $sItem->scriptoProject());
        $view->setVariable('item', $sItem->item());
        $view->setVariable('batchForm', $batchForm);
        return $view;
    }

    public function showDetailsAction()
    {
        $sMedia = $this->getScriptoRepresentation(
            $this->params('project-id'),
            $this->params('item-id'),
            $this->params('media-id')
        );
        if (!$sMedia) {
            exit;
        }

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        return $view;
    }

    public function showAction()
    {
        $sMedia = $this->getScriptoRepresentation(
            $this->params('project-id'),
            $this->params('item-id'),
            $this->params('media-id')
        );
        if (!$sMedia) {
            return $this->redirect()->toRoute('admin/scripto-project');
        }

        $sItem = $sMedia->scriptoItem();
        $view = new ViewModel;
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('sItem', $sItem);
        $view->setVariable('item', $sItem->item());
        return $view;
    }

    public function batchReviewAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(BatchReviewMediaForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $action = $this->params()->fromPost('batch-review-action');

                $allActions = ['approve-all', 'unapprove-all', 'complete-all', 'uncomplete-all'];
                $approvalActions = ['approve-all', 'approve-selected', 'unapprove-all', 'unapprove-selected'];
                $positiveActions = ['approve-all', 'approve-selected', 'complete-all', 'complete-selected'];

                $sMediaIds = $this->params()->fromPost('resource_ids', []);
                if (in_array($action, $allActions)) {
                    $sItem = $this->getScriptoRepresentation(
                        $this->params('project-id'),
                        $this->params('item-id')
                    );
                    $sMediaIds = $this->api()->search(
                        'scripto_media',
                        ['scripto_item_id' => $sItem->id()],
                        ['returnScalar' => 'id']
                    )->getContent();
                }

                $dataKey = in_array($action, $approvalActions)
                    ? 'o-module-scripto:is_approved' : 'o-module-scripto:is_completed';
                $dataValue = in_array($action, $positiveActions) ? true : false;

                $this->api()->batchUpdate('scripto_media', $sMediaIds, [$dataKey => $dataValue]);
                $this->messenger()->addSuccess('Scripto media successfully edited'); // @translate
                return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
            }
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }
}
