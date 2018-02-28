<?php
namespace Scripto\Controller\Admin;

use Scripto\Form\RevertRevisionForm;
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

        $form = $this->getForm(RevertRevisionForm::class);
        $revision = $sMedia->pageRevision($this->params('revision-id'));

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = ['o-module-scripto:text' => $revision['content']];
                $response = $this->api()->update('scripto_media', $sMedia->id(), $data, ['isPartial' => true]);
                if ($response) {
                    $this->messenger()->addSuccess('Scripto media revision successfully reverted.'); // @translate
                    return $this->redirect()->toRoute('admin/scripto-revision', ['action' => 'browse'], true);
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $page = $sMedia->page();
        $latestRevision = $page['revisions'][0];
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('revision', $sMedia->pageRevision($this->params('revision-id')));
        $view->setVariable('compare', $this->apiClient->compareRevisions($latestRevision['revid'], $revision['revid']));
        $view->setVariable('revisionHtml', $this->apiClient->parseRevision($this->params('revision-id')));
        $view->setVariable('form', $form);
        return $view;
    }

    public function revertAction()
    {
        $sMedia = $this->getScriptoRepresentation(
            $this->params('project-id'),
            $this->params('item-id'),
            $this->params('media-id')
        );
        if (!$sMedia) {
            return $this->redirect()->toRoute('admin/scripto-project');
        }

        $form = $this->getForm(RevertRevisionForm::class);
        $revision = $sMedia->pageRevision($this->params('revision-id'));

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = ['o-module-scripto:text' => $revision['content']];
                $response = $this->api()->update('scripto_media', $sMedia->id(), $data, ['isPartial' => true]);
                if ($response) {
                    $this->messenger()->addSuccess('Scripto media revision successfully reverted.'); // @translate
                    return $this->redirect()->toRoute('admin/scripto-revision', ['action' => 'browse'], true);
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $page = $sMedia->page();
        $latestRevision = $page['revisions'][0];
        $view = new ViewModel;
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('revision', $revision);
        $view->setVariable('compare', $this->apiClient->compareRevisions($latestRevision['revid'], $revision['revid']));
        $view->setVariable('form', $form);
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
