<?php
namespace Scripto\Controller\PublicApp;

use Scripto\Form\MediaPublicAppForm;
use Scripto\Mediawiki\Exception\EditException as MediawikiEditException;
use Scripto\Mediawiki\Exception\RequestException as MediawikiRequestException;
use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class MediaController extends AbstractActionController
{
    public function browseAction()
    {
        $sItem = $this->scripto()->getRepresentation(
            $this->params('project-id'),
            $this->params('item-id')
        );
        if (!$sItem) {
            return $this->redirect()->toRoute('site/scripto');
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
            return $this->redirect()->toRoute('site/scripto');
        }

        $sItem = $sMedia->scriptoItem();
        $project = $sItem->scriptoProject();
        $view = new ViewModel;
        $view->setVariable('userCanEdit', $sMedia->userCanEdit($namespace));
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
            return $this->redirect()->toRoute('site/scripto');
        }

        $action = (0 === $namespace) ? 'show' : 'talk';
        $mediaForm = $this->getForm(MediaPublicAppForm::class);

        $userCanEdit = $sMedia->userCanEdit($namespace);

        if (!$this->getRequest()->isPost()) {
            if (!$userCanEdit) {
                // Deny access to users without edit authorization. Say so,
                // rather than bouncing silently: MediaWiki withdraws
                // authorization from rate-limited users, and a transcriber
                // whose Edit link simply stops working has no way to tell that
                // from the site being broken.
                $this->messenger()->addError('MediaWiki is not allowing you to edit this page. If you were able to edit it a moment ago, this may be a temporary limit on your edit rate, and trying again shortly will work.'); // @translate
                return $this->redirect()->toRoute(null, ['action' => $action], true);
            }
            // Set media form for display.
            $mediaForm->setData([
                'wikitext' => $sMedia->pageWikitext($namespace),
            ]);
            return $this->editView($sMedia, $mediaForm);
        }

        // MediaWiki withdraws edit authorization from rate-limited users, so it
        // can be gone by the time a form that was authorized when rendered is
        // submitted. Redirecting now would discard the wikitext, so every
        // failure below falls through and re-renders the form instead.
        $mediaForm->setData($this->getRequest()->getPost());
        if (!$mediaForm->isValid()) {
            $this->messenger()->addFormErrors($mediaForm);
        } elseif (!$userCanEdit) {
            // Covers both a user who never had authorization and one whose
            // authorization MediaWiki has withdrawn, so it must not assert a
            // rate limit as the cause.
            $this->messenger()->addError('MediaWiki is not allowing you to edit this page, so your changes were not saved. If you were able to edit it a moment ago, this may be a temporary limit on your edit rate, and saving again shortly will work.'); // @translate
        } elseif ($this->saveWikitext($sMedia, $namespace, $mediaForm)) {
            $this->messenger()->addSuccess('Scripto media successfully updated.'); // @translate
            return $this->redirect()->toRoute(null, ['action' => $action], true);
        }
        return $this->editView($sMedia, $mediaForm);
    }

    /**
     * Save submitted wikitext to MediaWiki.
     *
     * Talk pages are edited through the MediaWiki client directly, while Main
     * pages go through Omeka's API. Both can fail by throwing, and the API can
     * also fail by returning false, so this reports the outcome as a boolean
     * and messages the reason itself.
     *
     * A failure is often recoverable, an edit rate limit for example, so the
     * caller must re-render the form rather than redirect. Otherwise the user
     * loses the wikitext they just submitted.
     *
     * @param \Scripto\Api\Representation\ScriptoMediaRepresentation $sMedia
     * @param int $namespace 0=Main; 1=Talk
     * @param \Scripto\Form\MediaPublicAppForm $mediaForm
     * @return bool Whether the wikitext was saved
     */
    protected function saveWikitext($sMedia, $namespace, $mediaForm)
    {
        $formData = $mediaForm->getData();
        $data = [
            'o-module-scripto:wikitext' => $formData['wikitext'],
            'o-module-scripto:summary' => $formData['summary'],
        ];
        if ($formData['mark_complete']) {
            $data['o-module-scripto:is_completed'] = true;
        }

        try {
            if (1 === $namespace) {
                $this->scripto()->apiClient()->editPage(
                    $sMedia->pageTitle(1), $formData['wikitext'], $formData['summary']
                );
                return true;
            }
            // Returns false on validation error, having messaged it already.
            return (bool) $this->api($mediaForm)->update('scripto_media', $sMedia->id(), $data);
        } catch (MediawikiEditException $e) {
            // MediaWiki refused the edit and its message is written for the
            // person who made it, so pass it through: "exceeds the page size
            // limit", "exceeded your rate limit", and so on.
            $this->messenger()->addError($e->getMessage());
            return false;
        } catch (MediawikiRequestException $e) {
            // A transport failure message can name internal hosts and ports, so
            // log it and tell the user only what they can act on.
            $this->logger()->err((string) $e);
            $this->messenger()->addError('Could not reach MediaWiki to save this page. Your changes were not saved. Please try again in a moment.'); // @translate
            return false;
        }
    }

    /**
     * Build the view model for an edit page.
     *
     * @param \Scripto\Api\Representation\ScriptoMediaRepresentation $sMedia
     * @param \Scripto\Form\MediaPublicAppForm $mediaForm
     * @return ViewModel
     */
    protected function editView($sMedia, $mediaForm)
    {
        $sItem = $sMedia->scriptoItem();
        $project = $sItem->scriptoProject();

        $this->layout()->setVariables([
            'project' => $project,
            'sItem' => $sItem,
            'sMedia' => $sMedia,
        ]);

        return new ViewModel([
            'mediaForm' => $mediaForm,
            'sMedia' => $sMedia,
            'media' => $sMedia->media(),
            'sItem' => $sItem,
            'item' => $sItem->item(),
            'project' => $project,
        ]);
    }
}
