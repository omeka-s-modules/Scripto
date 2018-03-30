<?php
namespace Scripto\Controller\Admin;

use Scripto\Form\BatchMediaForm;
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

        $batchForm = $this->getForm(BatchMediaForm::class, [
            'batch-manage-formaction' => (string) $this->url()->fromRoute(null, ['action' => 'batch-manage'], true),
            'batch-protect-formaction' => (string) $this->url()->fromRoute(null, ['action' => 'batch-protect'], true),
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

        $sItem = $sMedia->scriptoItem();
        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('sItem', $sItem);
        $view->setVariable('item', $sItem->item());
        $view->setVariable('project', $sItem->scriptoProject());
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
        $view->setVariable('project', $sItem->scriptoProject());
        return $view;
    }

    public function batchManageAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(BatchMediaForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $action = $this->params()->fromPost('batch-manage-action');

                $reviewActions = [
                    'approve-all', 'approve-selected',
                    'unapprove-all', 'unapprove-selected',
                    'complete-all', 'complete-selected',
                    'uncomplete-all', 'uncomplete-selected',
                ];
                $watchActions = [
                    'watch-all', 'unwatch-all',
                    'watch-selected', 'unwatch-selected',
                ];
                $allActions = [
                    'approve-all', 'unapprove-all',
                    'complete-all', 'uncomplete-all',
                    'watch-all', 'unwatch-all',
                ];
                $approvalActions = [
                    'approve-all', 'approve-selected',
                    'unapprove-all', 'unapprove-selected',
                ];
                $positiveActions = [
                    'approve-all', 'approve-selected',
                    'complete-all', 'complete-selected',
                ];

                // Get the Scripto media IDs.
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

                // Handle review actions.
                if (in_array($action, $reviewActions)) {
                    $dataKey = in_array($action, $approvalActions)
                        ? 'o-module-scripto:is_approved'
                        : 'o-module-scripto:is_completed';
                    $this->api()->batchUpdate(
                        'scripto_media',
                        $sMediaIds,
                        [$dataKey => in_array($action, $positiveActions)]
                    );
                // Handle watch actions.
                } elseif (in_array($action, $watchActions)) {
                    $titles = [];
                    foreach ($sMediaIds as $sMediaId) {
                        $sMedia = $this->api()->read('scripto_media', $sMediaId)->getContent();
                        $titles[] = $sMedia->pageTitle();
                    }
                    if (in_array($action, ['watch-all', 'watch-selected'])) {
                        $this->scriptoApiClient()->watchPages($titles);
                    } elseif (in_array($action, ['unwatch-all', 'unwatch-selected'])) {
                        $this->scriptoApiClient()->unwatchPages($titles);
                    }
                }

                $this->messenger()->addSuccess('Scripto media successfully reviewed'); // @translate
                return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
            }
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    public function batchProtectAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(BatchMediaForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $action = $this->params()->fromPost('batch-protect-action');
                $expiry = $this->params()->fromPost('batch-protect-expiry') ?: 'never';

                $titles = ['created' => [], 'not_created' => []];
                $sMediaIds = $this->params()->fromPost('resource_ids', []);
                foreach ($sMediaIds as $sMediaId) {
                    $sMedia = $this->api()->read('scripto_media', $sMediaId)->getContent();
                    $title = $sMedia->pageTitle();
                    if ($sMedia->pageIsCreated()) {
                        $titles['created'][] = $title;
                    } else {
                        $titles['not_created'][] = $title;
                    }
                }

                $this->scriptoApiClient()->protectPages($titles['created'], 'edit', $action, $expiry);
                $this->scriptoApiClient()->protectPages($titles['not_created'], 'create', $action, $expiry);

                $this->messenger()->addSuccess('Scripto media successfully protected'); // @translate
                return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
            }
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }
}
