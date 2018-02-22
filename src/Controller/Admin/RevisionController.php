<?php
namespace Scripto\Controller\Admin;

use Scripto\Mediawiki\ApiClient;
use Zend\View\Model\ViewModel;

class RevisionController extends AbstractScriptoController
{
    /**
     * @var ApiClient
     */
    protected $apiClient;

    /**
     * @param ApiClient $apiClient
     */
    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function browseAction()
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
        $view->setVariable('revisions', $sMedia->pageRevisions());
        $view->setVariable('sItem', $sItem);
        $view->setVariable('item', $sItem->item());
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

        $view = new ViewModel;
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('revision', $sMedia->pageRevision($this->params('revision-id')));
        $view->setVariable('revisionHtml', $this->apiClient->parseRevision($this->params('revision-id')));
        return $view;
    }

    public function compareAction()
    {
        $sMedia = $this->getScriptoRepresentation(
            $this->params('project-id'),
            $this->params('item-id'),
            $this->params('media-id')
        );
        if (!$sMedia) {
            return $this->redirect()->toRoute('admin/scripto-project');
        }

        $view = new ViewModel;
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('fromRevision', $sMedia->pageRevision($this->params('from-revision-id')));
        $view->setVariable('toRevision', $sMedia->pageRevision($this->params('to-revision-id')));
        $view->setVariable('compare', $this->apiClient->compareRevisions($this->params('from-revision-id'), $this->params('to-revision-id')));
        return $view;
    }
}
