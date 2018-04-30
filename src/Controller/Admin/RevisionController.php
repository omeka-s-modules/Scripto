<?php
namespace Scripto\Controller\Admin;

use Scripto\Form\RevisionRevertForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class RevisionController extends AbstractActionController
{
    public function browseAction()
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
        $response = $sMedia->pageRevisions(100, $this->params()->fromQuery('continue'));
        $revisions = isset($response['query']['pages'][0]['revisions'])
            ? $response['query']['pages'][0]['revisions'] : [];
        $continue = isset($response['continue']) ? $response['continue']['rvcontinue'] : null;

        $view = new ViewModel;
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('sItem', $sItem);
        $view->setVariable('item', $sItem->item());
        $view->setVariable('revisions', $revisions);
        $view->setVariable('continue', $continue);
        return $view;
    }

    public function compareAction()
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
        $view->setVariable('fromRevision', $sMedia->pageRevision($this->params('from-revision-id')));
        $view->setVariable('toRevision', $sMedia->pageRevision($this->params('to-revision-id')));
        $view->setVariable('compare', $this->scripto()->apiClient()->compareRevisions($this->params('from-revision-id'), $this->params('to-revision-id')));
        return $view;
    }
}
