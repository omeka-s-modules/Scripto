<?php
namespace Scripto\Controller\PublicApp;

use Scripto\Mediawiki\Exception\QueryException;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class RevisionController extends AbstractActionController
{
    public function browseAction()
    {
        return $this->handleBrowse(0);
    }

    public function browseTalkAction()
    {
        return $this->handleBrowse(1);
    }

    public function showAction()
    {
        return $this->handleShow(0);
    }

    public function showTalkAction()
    {
        return $this->handleShow(1);
    }

    public function compareAction()
    {
        return $this->handleCompare(0);
    }

    public function compareTalkAction()
    {
        return $this->handleCompare(1);
    }

    /**
     * Handle the browse actions for the Main and Talk namespaces.
     *
     * @param int $namespace 0=Main; 1=Talk
     */
    public function handleBrowse($namespace)
    {
        $sMedia = $this->scripto()->getRepresentation(
            $this->params('project-id'),
            $this->params('item-id'),
            $this->params('media-id')
        );
        if (!$sMedia) {
            return $this->redirect()->toRoute('scripto');
        }

        $response = $sMedia->pageRevisions($namespace, 100, $this->params()->fromQuery('continue'));
        $revisions = isset($response['query']['pages'][0]['revisions'])
            ? $response['query']['pages'][0]['revisions'] : [];
        $continue = isset($response['continue']) ? $response['continue']['rvcontinue'] : null;

        $sItem = $sMedia->scriptoItem();
        $project = $sItem->scriptoProject();
        $view = new ViewModel;
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('sItem', $sItem);
        $view->setVariable('item', $sItem->item());
        $view->setVariable('project', $project);
        $view->setVariable('revisions', $revisions);
        $view->setVariable('continue', $continue);
        $view->setVariable('userCanEdit', $sMedia->userCanEdit($namespace));
        $this->layout()->setVariable('project', $project);
        $this->layout()->setVariable('sItem', $sItem);
        $this->layout()->setVariable('sMedia', $sMedia);
        return $view;
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

        try {
            $revision = $sMedia->pageRevision($namespace, $this->params('revision-id'));
        } catch (QueryException $e) {
            // Invalid revision ID
            return $this->redirect()->toRoute('admin/scripto');
        }

        $sItem = $sMedia->scriptoItem();
        $project = $sItem->scriptoProject();
        $view = new ViewModel;
        $view->setVariable('revision', $revision);
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('sItem', $sItem);
        $view->setVariable('item', $sItem->item());
        $view->setVariable('project', $project);
        $view->setVariable('userCanEdit', $sMedia->userCanEdit($namespace));
        $this->layout()->setVariable('project', $project);
        $this->layout()->setVariable('sItem', $sItem);
        $this->layout()->setVariable('sMedia', $sMedia);

        return $view;
    }

    /**
     * Handle the compare actions for the Main and Talk namespaces.
     *
     * @param int $namespace 0=Main; 1=Talk
     */
    public function handleCompare($namespace)
    {
        $sMedia = $this->scripto()->getRepresentation(
            $this->params('project-id'),
            $this->params('item-id'),
            $this->params('media-id')
        );
        if (!$sMedia) {
            return $this->redirect()->toRoute('admin/scripto');
        }

        $fromRevision = $sMedia->pageRevision($namespace, $this->params('from-revision-id'));
        $toRevision = $sMedia->pageRevision($namespace, $this->params('to-revision-id'));
        $compare = $this->scripto()->apiClient()->compareRevisions(
            $this->params('from-revision-id'),
            $this->params('to-revision-id')
        );

        $sItem = $sMedia->scriptoItem();
        $project = $sItem->scriptoProject();
        $view = new ViewModel;
        $view->setVariable('fromRevision', $fromRevision);
        $view->setVariable('toRevision', $toRevision);
        $view->setVariable('compare', $compare);
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('sItem', $sItem);
        $view->setVariable('item', $sItem->item());
        $view->setVariable('project', $project);
        $view->setVariable('userCanEdit', $sMedia->userCanEdit($namespace));
        $this->layout()->setVariable('project', $project);
        $this->layout()->setVariable('sItem', $sItem);
        $this->layout()->setVariable('sMedia', $sMedia);
        return $view;
    }
}
