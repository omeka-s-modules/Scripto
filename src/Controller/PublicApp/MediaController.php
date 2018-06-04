<?php
namespace Scripto\Controller\PublicApp;

use Scripto\Form\MediaPublicAppForm;
use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class MediaController extends AbstractActionController
{
    public function browseAction()
    {
        $sItem = $this->scripto()->getRepresentation(
            $this->params('project-id'),
            $this->params('item-id')
        );
        if (!$sItem) {
            return $this->redirect()->toRoute('scripto');
        }

        $this->setBrowseDefaults('position', 'asc');
        $query = array_merge(
            ['scripto_item_id' => $sItem->id()],
            $this->params()->fromQuery()
        );
        $response = $this->api()->search('scripto_media', $query);
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $sMedias = $response->getContent();
        $this->scripto()->cacheMediawikiPages($sMedias);

        $project = $sItem->scriptoProject();
        $view = new ViewModel;
        $view->setVariable('sMedias', $sMedias);
        $view->setVariable('sItem', $sItem);
        $view->setVariable('item', $sItem->item());
        $view->setVariable('project', $project);
        $this->layout()->setVariable('project', $project);
        $this->layout()->setVariable('sItem', $sItem);
        return $view;
    }

    public function showAction()
    {
        return $this->handleShow(0);
    }

    public function showTalkAction()
    {
        return $this->handleShow(1);
    }

    public function editAction()
    {
        return $this->handleEdit(0);
    }

    public function editTalkAction()
    {
        return $this->handleEdit(1);
    }

    /**
     * Handle the show actions for the Main and Talk namespaces.
     *
     * @param int $namespace 0=Main; 1=Talk
     */
    public function handleShow($namespace)
    {
        $sMedia = $this->scripto()->getRepresentation(
            $this->params('project-id'),
            $this->params('item-id'),
            $this->params('media-id')
        );
        if (!$sMedia) {
            return $this->redirect()->toRoute('scripto');
        }

        $mediaForm = $this->getForm(MediaPublicAppForm::class);
        $userIsLoggedIn = $this->scripto()->apiClient()->userIsLoggedIn();
        $userCanEdit = $sMedia->userCanEdit($namespace);

        if ($this->getRequest()->isPost()) {
            $mediaForm->setData($this->getRequest()->getPost());
            if ($mediaForm->isValid()) {
                $formData = $mediaForm->getData();
                $data = [];
                // Update MediaWiki data.
                if ($userIsLoggedIn) {
                    if ($formData['is_watched']) {
                        $this->scripto()->apiClient()->watchPage($sMedia->pageTitle($namespace));
                    } else {
                        $this->scripto()->apiClient()->unwatchPage($sMedia->pageTitle($namespace));
                    }
                }
                $this->messenger()->addSuccess('Scripto media successfully updated.'); // @translate
                $action = (0 === $namespace) ? 'show' : 'show-talk';
                return $this->redirect()->toRoute(null, ['action' => $action], true);
            } else {
                $this->messenger()->addFormErrors($mediaForm);
            }
        }

        // Set media form for display.
        $data = [
            'is_watched' => $sMedia->isWatched($namespace),
        ];
        $mediaForm->setData($data);

        $sItem = $sMedia->scriptoItem();
        $project = $sItem->scriptoProject();
        $view = new ViewModel;
        $view->setVariable('userCanEdit', $userCanEdit);
        $view->setVariable('userIsLoggedIn', $userIsLoggedIn);
        $view->setVariable('mediaForm', $mediaForm);
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('sItem', $sItem);
        $view->setVariable('item', $sItem->item());
        $view->setVariable('project', $project);
        $this->layout()->setVariable('project', $project);
        $this->layout()->setVariable('sItem', $sItem);
        $this->layout()->setVariable('sMedia', $sMedia);
        return $view;
    }

    /**
     * Handle the edit actions for the Main and Talk namespaces.
     *
     * @param int $namespace 0=Main; 1=Talk
     */
    public function handleEdit($namespace)
    {
        $sMedia = $this->scripto()->getRepresentation(
            $this->params('project-id'),
            $this->params('item-id'),
            $this->params('media-id')
        );
        if (!$sMedia) {
            return $this->redirect()->toRoute('scripto');
        }

        $mediaForm = $this->getForm(MediaPublicAppForm::class);
        $userIsLoggedIn = $this->scripto()->apiClient()->userIsLoggedIn();
        $userCanEdit = $sMedia->userCanEdit($namespace);

        if (!$userCanEdit) {
            // Deny access to users without edit authorization.
            return $this->redirect()->toRoute(null, ['action' => 'show-talk'], true);
        }

        if ($this->getRequest()->isPost()) {
            $mediaForm->setData($this->getRequest()->getPost());
            if ($mediaForm->isValid()) {
                $formData = $mediaForm->getData();
                $data = [];
                // Update MediaWiki data.
                if ($userIsLoggedIn) {
                    if ($formData['is_watched']) {
                        $this->scripto()->apiClient()->watchPage($sMedia->pageTitle($namespace));
                    } else {
                        $this->scripto()->apiClient()->unwatchPage($sMedia->pageTitle($namespace));
                    }
                }
                // Update Scripto media.
                if (1 === $namespace) {
                    $this->scripto()->apiClient()->editPage(
                        $sMedia->pageTitle(1),
                        $formData['wikitext'],
                        $formData['summary']
                    );
                } else {
                    if ($formData['mark_complete']) {
                        $data['o-module-scripto:is_completed'] = true;
                    }
                    $data['o-module-scripto:wikitext'] = $formData['wikitext'];
                    $data['o-module-scripto:summary'] = $formData['summary'];
                    $response = $this->api($mediaForm)->update('scripto_media', $sMedia->id(), $data);
                }
                $this->messenger()->addSuccess('Scripto media successfully updated.'); // @translate
                $action = (0 === $namespace) ? 'show' : 'show-talk';
                return $this->redirect()->toRoute(null, ['action' => $action], true);
            } else {
                $this->messenger()->addFormErrors($mediaForm);
            }
        }

        // Set media form for display.
        $data = [
            'wikitext' => $sMedia->pageWikitext($namespace),
            'is_watched' => $sMedia->isWatched($namespace),
        ];
        $mediaForm->setData($data);

        $sItem = $sMedia->scriptoItem();
        $project = $sItem->scriptoProject();
        $view = new ViewModel;
        $view->setVariable('userCanEdit', $userCanEdit);
        $view->setVariable('userIsLoggedIn', $userIsLoggedIn);
        $view->setVariable('mediaForm', $mediaForm);
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('sItem', $sItem);
        $view->setVariable('item', $sItem->item());
        $view->setVariable('project', $project);
        $this->layout()->setVariable('project', $project);
        $this->layout()->setVariable('sItem', $sItem);
        $this->layout()->setVariable('sMedia', $sMedia);
        return $view;
    }
}
