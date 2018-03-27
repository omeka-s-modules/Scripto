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
            'batch-review-formaction' => (string) $this->url()->fromRoute(null, ['action' => 'batch-review'], true),
            'batch-manage-formaction' => (string) $this->url()->fromRoute(null, ['action' => 'batch-manage'], true),
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

    public function batchReviewAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(BatchMediaForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $action = $this->params()->fromPost('batch-review-action');

                $allActions = ['approve-all', 'unapprove-all', 'complete-all', 'uncomplete-all'];
                $approvalActions = ['approve-all', 'approve-selected', 'unapprove-all', 'unapprove-selected'];
                $positiveActions = ['approve-all', 'approve-selected', 'complete-all', 'complete-selected'];

                $dataKey = in_array($action, $approvalActions)
                    ? 'o-module-scripto:is_approved' : 'o-module-scripto:is_completed';
                $dataValue = in_array($action, $positiveActions) ? true : false;

                $sMediaIds = $this->getScriptoMediaIdsForBatch(in_array($action, $allActions));
                $this->api()->batchUpdate('scripto_media', $sMediaIds, [$dataKey => $dataValue]);
                $this->messenger()->addSuccess('Scripto media successfully reviewed'); // @translate
                return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
            }
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    public function batchManageAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(BatchMediaForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $action = $this->params()->fromPost('batch-manage-action');

                $allActions = ['watch-all', 'unwatch-all', 'restrict-admin-all', 'restrict-user-all', 'open-all'];
                $protectionActions = ['restrict-admin-selected', 'restrict-user-selected', 'open-selected'];

                $titles = ['all' => [], 'created' => [], 'not_created' => []];
                $sMediaIds = $this->getScriptoMediaIdsForBatch(in_array($action, $allActions));
                foreach ($sMediaIds as $sMediaId) {
                    $sMedia = $this->api()->read('scripto_media', $sMediaId)->getContent();
                    $title = $sMedia->pageTitle();
                    $titles['all'][] = $title;
                    // Register created and not created titles only if needed.
                    if (in_array($action, $protectionActions)) {
                        if ($sMedia->pageIsCreated()) {
                            $titles['created'][] = $title;
                        } else {
                            $titles['not_created'][] = $title;
                        }
                    }
                }

                if (in_array($action, ['watch-all', 'watch-selected'])) {
                    $this->scriptoApiClient()->watchPages($titles['all']);
                } elseif (in_array($action, ['unwatch-all', 'unwatch-selected'])) {
                    $this->scriptoApiClient()->unwatchPages($titles['all']);
                } elseif ($action === 'restrict-admin-selected') {
                    $this->scriptoApiClient()->protectPages($titles['created'], 'edit', 'sysop');
                    $this->scriptoApiClient()->protectPages($titles['not_created'], 'create', 'sysop');
                } elseif ($action === 'restrict-user-selected') {
                    $this->scriptoApiClient()->protectPages($titles['created'], 'edit', 'autoconfirmed');
                    $this->scriptoApiClient()->protectPages($titles['not_created'], 'create', 'autoconfirmed');
                } elseif ($action === 'open-selected') {
                    $this->scriptoApiClient()->protectPages($titles['created'], 'edit', 'all');
                    $this->scriptoApiClient()->protectPages($titles['not_created'], 'create', 'all');
                }

                $this->messenger()->addSuccess('Scripto media successfully managed'); // @translate
                return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
            }
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    /**
     * Get Scripto Media IDs for batch actions.
     *
     * @param bool $getAll True: get all IDs; false: get from posted resource_ids
     * @return array
     */
    public function getScriptoMediaIdsForBatch($getAll)
    {
        $sMediaIds = $this->params()->fromPost('resource_ids', []);
        if ($getAll) {
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
        return $sMediaIds;
    }
}
