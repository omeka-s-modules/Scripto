<?php
namespace Scripto\Controller\Admin;

use Scripto\Form\MediaBatchForm;
use Scripto\Form\MediaForm;
use Scripto\Form\RevisionRevertForm;
use Scripto\Mediawiki\Exception\QueryException;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class MediaController extends AbstractActionController
{
    public function browseAction()
    {
        $sItem = $this->scripto()->getRepresentation(
            $this->params('project-id'),
            $this->params('item-id')
        );
        if (!$sItem) {
            return $this->redirect()->toRoute('admin/scripto');
        }

        $this->setBrowseDefaults('position', 'asc');
        $query = array_merge(
            ['scripto_item_id' => $sItem->id()],
            $this->params()->fromQuery()
        );
        $response = $this->api()->search('scripto_media', $query);
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $sMedia = $response->getContent();
        $this->scripto()->cacheMediawikiPages($sMedia);

        $view = new ViewModel;
        $view->setVariable('sItem', $sItem);
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('project', $sItem->scriptoProject());
        $view->setVariable('item', $sItem->item());
        return $view;
    }

    public function showDetailsAction()
    {
        $sMedia = $this->scripto()->getRepresentation(
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
        $sMedia = $this->scripto()->getRepresentation(
            $this->params('project-id'),
            $this->params('item-id'),
            $this->params('media-id')
        );
        if (!$sMedia) {
            return $this->redirect()->toRoute('admin/scripto');
        }

        try {
            $revision = $sMedia->pageRevision(0, $this->params('revision-id'));
        } catch (QueryException $e) {
            // Invalid revision ID
            return $this->redirect()->toRoute('admin/scripto');
        }

        $revertForm = $this->getForm(RevisionRevertForm::class);
        $mediaForm = $this->getForm(MediaForm::class);
        $editAccess = $sMedia->editAccess(0);
        $postData = $this->getRequest()->getPost();

        // Handle the revision revert form.
        if ($this->getRequest()->isPost() && isset($postData['submit_revisionrevertform'])) {
            $revertForm->setData($postData);
            if ($revertForm->isValid()) {
                $data = ['o-module-scripto:wikitext' => $revision['content']];
                $response = $this->api()->update('scripto_media', $sMedia->id(), $data, ['isPartial' => true]);
                if ($response) {
                    $this->messenger()->addSuccess('Scripto media revision successfully reverted.'); // @translate
                    return $this->redirect()->toRoute(null, ['revision-id' => null], true);
                }
            } else {
                $this->messenger()->addFormErrors($revertForm);
            }
        }

        // Handle the media form.
        if ($this->getRequest()->isPost() && isset($postData['submit_mediaform'])) {
            $mediaForm->setData($postData);
            if ($mediaForm->isValid()) {
                $formData = $mediaForm->getData();

                // Update MediaWiki data.
                if ($sMedia->userCan(0, 'protect')) {
                    if ('' === $formData['protection_expiry']) {
                        // Use existing expiration date.
                        $protectionExpiry = $editAccess['expiry']
                            ? $editAccess['expiry']->format('c')
                            : 'infinite';
                    } else {
                        // Use selected expiration date.
                        $protectionExpiry = $formData['protection_expiry'];
                    }
                    $this->scripto()->apiClient()->protectPage(
                        $sMedia->pageTitle(0),
                        $sMedia->pageIsCreated(0) ? 'edit' : 'create',
                        $formData['protection_level'],
                        $protectionExpiry
                    );
                }

                // Update Scripto media.
                $data = [];
                if ($formData['toggle_complete']) {
                    $data['o-module-scripto:is_completed'] = $sMedia->completed() ? false : true;
                    $data['o-module-scripto:completed_revision'] = $revision ? $revision['revid'] : null;
                }
                if ('complete' === $formData['complete_action']) {
                    $data['o-module-scripto:is_completed'] = true;
                    $data['o-module-scripto:completed_revision'] = $revision ? $revision['revid'] : null;
                } elseif ('not_complete' === $formData['complete_action']) {
                    $data['o-module-scripto:is_completed'] = false;
                }
                if ($formData['toggle_approved']) {
                    $data['o-module-scripto:is_approved'] = $sMedia->approved() ? false : true;
                    $data['o-module-scripto:approved_revision'] = $revision ? $revision['revid'] : null;
                }
                if ('approved' === $formData['approved_action']) {
                    $data['o-module-scripto:is_approved'] = true;
                    $data['o-module-scripto:approved_revision'] = $revision ? $revision['revid'] : null;
                } elseif ('not_approved' === $formData['approved_action']) {
                    $data['o-module-scripto:is_approved'] = false;
                }
                $response = $this->api($mediaForm)->update('scripto_media', $sMedia->id(), $data);
                if ($response) {
                    $this->messenger()->addSuccess('Scripto media successfully updated.'); // @translate
                } else {
                    $this->messenger()->addFormErrors($mediaForm);
                }
                return $this->redirect()->toRoute(null, [], true);
            } else {
                $this->messenger()->addFormErrors($mediaForm);
            }
        }

        // Set media form data.
        $data = [];
        if (!$editAccess['expired']) {
            $data['protection_level'] = $editAccess['level'];
            $mediaForm->get('protection_expiry')->setEmptyOption(sprintf(
                $this->translate('Existing expiration time: %s'),
                $editAccess['expiry']
                    ? $editAccess['expiry']->format('G:i, j F Y')
                    : 'infinite'
            ));
        }
        $mediaForm->setData($data);

        $sItem = $sMedia->scriptoItem();
        $revisionId = isset($revision['revid']) ? $revision['revid'] : null;
        $view = new ViewModel;
        $view->setVariable('revision', $revision);
        $view->setVariable('latestRevision', $sMedia->pageLatestRevision(0));
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('sItem', $sItem);
        $view->setVariable('item', $sItem->item());
        $view->setVariable('project', $sItem->scriptoProject());
        $view->setVariable('mediaForm', $mediaForm);
        $view->setVariable('revertForm', $revertForm);
        return $view;
    }

    public function showTalkaction()
    {
        $sMedia = $this->scripto()->getRepresentation(
            $this->params('project-id'),
            $this->params('item-id'),
            $this->params('media-id')
        );
        if (!$sMedia) {
            return $this->redirect()->toRoute('admin/scripto');
        }

        try {
            $revision = $sMedia->pageRevision(1, $this->params('revision-id'));
        } catch (QueryException $e) {
            // Invalid revision ID
            return $this->redirect()->toRoute('admin/scripto');
        }

        $revertForm = $this->getForm(RevisionRevertForm::class);
        $mediaForm = $this->getForm(MediaForm::class);
        $editAccess = $sMedia->editAccess(1);
        $postData = $this->getRequest()->getPost();

        // Handle the revision revert form.
        if ($this->getRequest()->isPost() && isset($postData['submit_revisionrevertform'])) {
            $revertForm->setData($postData);
            if ($revertForm->isValid()) {
                $this->scripto()->apiClient()->editPage($sMedia->pageTitle(1), $revision['content']);
                $this->messenger()->addSuccess('Scripto media revision successfully reverted.'); // @translate
                return $this->redirect()->toRoute(null, ['revision-id' => null], true);
            } else {
                $this->messenger()->addFormErrors($revertForm);
            }
        }

        // Handle the media form.
        if ($this->getRequest()->isPost() && isset($postData['submit_mediaform'])) {
            $mediaForm->setData($postData);
            if ($mediaForm->isValid()) {
                $formData = $mediaForm->getData();

                // Update MediaWiki data.
                if ($sMedia->userCan(1, 'protect')) {
                    if ('' === $formData['protection_expiry']) {
                        // Use existing expiration date.
                        $protectionExpiry = $editAccess['expiry']
                            ? $editAccess['expiry']->format('c')
                            : 'infinite';
                    } else {
                        // Use selected expiration date.
                        $protectionExpiry = $formData['protection_expiry'];
                    }
                    $this->scripto()->apiClient()->protectPage(
                        $sMedia->pageTitle(1),
                        $sMedia->pageIsCreated(1) ? 'edit' : 'create',
                        $formData['protection_level'],
                        $protectionExpiry
                    );
                }

                $this->messenger()->addSuccess('Scripto media successfully updated.'); // @translate
                return $this->redirect()->toRoute(null, [], true);
            } else {
                $this->messenger()->addFormErrors($mediaForm);
            }
        }

        // Set media form data.
        $data = [];
        if (!$editAccess['expired']) {
            $data['protection_level'] = $editAccess['level'];
            $mediaForm->get('protection_expiry')->setEmptyOption(sprintf(
                $this->translate('Existing expiration time: %s'),
                $editAccess['expiry']
                    ? $editAccess['expiry']->format('G:i, j F Y')
                    : 'infinite'
            ));
        }
        $mediaForm->setData($data);

        $sItem = $sMedia->scriptoItem();
        $revisionId = isset($revision['revid']) ? $revision['revid'] : null;
        $view = new ViewModel;
        $view->setVariable('revision', $revision);
        $view->setVariable('latestRevision', $sMedia->pageLatestRevision(1));
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('sItem', $sItem);
        $view->setVariable('item', $sItem->item());
        $view->setVariable('project', $sItem->scriptoProject());
        $view->setVariable('mediaForm', $mediaForm);
        $view->setVariable('revertForm', $revertForm);
        return $view;
    }

    public function batchEditAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
        }

        $sItem = $this->scripto()->getRepresentation(
            $this->params('project-id'),
            $this->params('item-id')
        );
        if (!$sItem) {
            return $this->redirect()->toRoute('admin/scripto');
        }

        $sMediaIds = $this->params()->fromPost('resource_ids', []);

        $sMedias = [];
        foreach ($sMediaIds as $sMediaId) {
            $sMedias[] = $this->api()->read('scripto_media', $sMediaId)->getContent();
        }

        $form = $this->getForm(MediaBatchForm::class);

        if ($this->params()->fromPost('batch_edit')) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();

                // Update MediaWiki data.
                if ($this->scripto()->apiClient()->userIsLoggedIn()) {
                    $titles = [];
                    foreach ($sMedias as $sMedia) {
                        $titles[] = $sMedia->pageTitle(0);
                    }
                    if ('1' === $formData['is_watched']) {
                        $this->scripto()->apiClient()->watchPages($titles);
                    } elseif ('0' === $formData['is_watched']) {
                        $this->scripto()->apiClient()->unwatchPages($titles);
                    }
                }
                if ($formData['protection_level'] && $this->scripto()->apiClient()->userIsInGroup('sysop')) {
                    foreach ($sMedias as $sMedia) {
                        $this->scripto()->apiClient()->protectPage(
                            $sMedia->pageTitle(0),
                            $sMedia->pageIsCreated(0) ? 'edit' : 'create',
                            $formData['protection_level'],
                            $formData['protection_expiry']
                        );
                    }
                }

                // Update Scripto media.
                $data = [];
                if ('1' === $formData['is_completed']) {
                    $data['o-module-scripto:is_completed'] = true;
                } elseif ('0' === $formData['is_completed']) {
                    $data['o-module-scripto:is_completed'] = false;
                }
                if ('1' === $formData['is_approved']) {
                    $data['o-module-scripto:is_approved'] = true;
                } elseif ('0' === $formData['is_approved']) {
                    $data['o-module-scripto:is_approved'] = false;
                }
                if ($data) {
                    $sMediaIds = [];
                    foreach ($sMedias as $sMedia) {
                        $sMediaIds[] = $sMedia->id();
                    }
                    $this->api()->batchUpdate('scripto_media', $sMediaIds, $data);
                }

                $this->messenger()->addSuccess('Scripto media successfully edited'); // @translate
                return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('sItem', $sItem);
        $view->setVariable('sMedias', $sMedias);
        $view->setVariable('project', $sItem->scriptoProject());
        $view->setVariable('form', $form);
        return $view;
    }

    public function batchEditAllAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
        }

        $sItem = $this->scripto()->getRepresentation(
            $this->params('project-id'),
            $this->params('item-id')
        );
        if (!$sItem) {
            return $this->redirect()->toRoute('admin/scripto');
        }

        // Note that we synchronously process a batch-edit with the request
        // instead of dispatching an asynchronous job (as is typical for
        // potentially large processes). This is because MediaWiki's API:Watch
        // and API:Protect require the user to be logged in, which is impossible
        // in a job.
        $query = json_decode($this->params()->fromPost('query', []), true);
        unset(
            $query['limit'], $query['offset'],
            $query['page'], $query['per_page'],
            $query['sort_by'], $query['sort_order']
        );
        $query['scripto_item_id'] = $sItem->id();
        $response = $this->api()->search('scripto_media', $query);
        $sMedias = $response->getContent();

        $form = $this->getForm(MediaBatchForm::class);

        if ($this->params()->fromPost('batch_edit')) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();

                // Update MediaWiki data. Note that we don't allow users to
                // modify protection status because the MediaWiki API doesn't
                // provide batch protections. Individual requests to API:Protect
                // are relatively slow and will compound with many requests.
                if ($this->scripto()->apiClient()->userIsLoggedIn()) {
                    $titles = [];
                    foreach ($sMedias as $sMedia) {
                        $titles[] = $sMedia->pageTitle(0);
                    }
                    if ('1' === $formData['is_watched']) {
                        $this->scripto()->apiClient()->watchPages($titles);
                    } elseif ('0' === $formData['is_watched']) {
                        $this->scripto()->apiClient()->unwatchPages($titles);
                    }
                }

                // Update Scripto media.
                $data = [];
                if ('1' === $formData['is_completed']) {
                    $data['o-module-scripto:is_completed'] = true;
                } elseif ('0' === $formData['is_completed']) {
                    $data['o-module-scripto:is_completed'] = false;
                }
                if ('1' === $formData['is_approved']) {
                    $data['o-module-scripto:is_approved'] = true;
                } elseif ('0' === $formData['is_approved']) {
                    $data['o-module-scripto:is_approved'] = false;
                }
                if ($data) {
                    $sMediaIds = [];
                    foreach ($sMedias as $sMedia) {
                        $sMediaIds[] = $sMedia->id();
                    }
                    $this->api()->batchUpdate('scripto_media', $sMediaIds, $data);
                }

                $this->messenger()->addSuccess('Scripto media successfully edited'); // @translate
                return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('sItem', $sItem);
        $view->setVariable('project', $sItem->scriptoProject());
        $view->setVariable('query', $query);
        $view->setVariable('count', $response->getTotalResults());
        $view->setVariable('form', $form);
        return $view;
    }

    public function watchAction()
    {
        if (!$this->getRequest()->isPost()) {
            $this->getResponse()->setStatusCode(400);
            return;
        }
        if (!$this->scripto()->apiClient()->userIsLoggedIn()) {
            $this->getResponse()->setStatusCode(403);
            return;
        }
        $sMedia = $this->scripto()->getRepresentation(
            $this->params('project-id'),
            $this->params('item-id'),
            $this->params('media-id')
        );
        if (!$sMedia) {
            $this->getResponse()->setStatusCode(400);
            return;
        }

        // Note that MediaWiki always watches and unwatches a Main page and its
        // Talk page simultaneously, so these's no need to make a distinction.
        $watching = $this->getRequest()->getPost('watching');
        if ($watching) {
            $this->scripto()->apiClient()->watchPage($sMedia->pageTitle(0));
        } else {
            $this->scripto()->apiClient()->unwatchPage($sMedia->pageTitle(0));
        }
        exit;
    }
}
