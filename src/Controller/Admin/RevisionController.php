<?php
namespace Scripto\Controller\Admin;

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
            return $this->redirect()->toRoute('admin/scripto');
        }

        $sItem = $sMedia->scriptoItem();
        $response = $sMedia->pageRevisions($namespace, 100, $this->params()->fromQuery('continue'));
        $revisions = isset($response['query']['pages'][0]['revisions'])
            ? $response['query']['pages'][0]['revisions'] : [];
        $continue = isset($response['continue']) ? $response['continue']['rvcontinue'] : null;

        $view = new ViewModel;
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('sItem', $sItem);
        $view->setVariable('project', $sItem->scriptoProject());
        $view->setVariable('item', $sItem->item());
        $view->setVariable('revisions', $revisions);
        $view->setVariable('continue', $continue);
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

        $view = new ViewModel;
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('project', $sMedia->scriptoItem()->scriptoProject());
        $view->setVariable('fromRevision', $sMedia->pageRevision($namespace, $this->params('from-revision-id')));
        $view->setVariable('toRevision', $sMedia->pageRevision($namespace, $this->params('to-revision-id')));
        $view->setVariable('compare', $this->scripto()->apiClient()->compareRevisions($this->params('from-revision-id'), $this->params('to-revision-id')));
        return $view;
    }
}
