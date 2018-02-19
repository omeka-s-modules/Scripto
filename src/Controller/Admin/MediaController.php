<?php
namespace Scripto\Controller\Admin;

use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class MediaController extends AbstractActionController
{
    public function browseAction()
    {
        $sItem = $this->api()->searchOne('scripto_items', [
            'scripto_project_id' => $this->params('project-id'),
            'item_id' => $this->params('item-id'),
        ])->getContent();

        $this->setBrowseDefaults('position', 'asc');
        $query = array_merge(
            ['scripto_item_id' => $sItem->id()],
            $this->params()->fromQuery()
        );
        $response = $this->api()->search('scripto_media', $query);
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $sMedia = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('sItem', $sItem);
        $view->setVariable('sMedia', $sMedia);
        return $view;
    }

    public function showDetailsAction()
    {
        $sItem = $this->api()->searchOne('scripto_items', [
            'scripto_project_id' => $this->params('project-id'),
            'item_id' => $this->params('item-id'),
        ])->getContent();
        if (!$sItem) {
            return $this->redirect()->toRoute('admin/scripto-project');
        }
        $sMedia = $this->api()->searchOne('scripto_media', [
            'scripto_item_id' => $sItem->id(),
            'media_id' => $this->params('media-id'),
        ])->getContent();
        if (!$sMedia) {
            return $this->redirect()->toRoute('admin/scripto-project');
        }

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        return $view;
    }

    public function showAction()
    {
        $sItem = $this->api()->searchOne('scripto_items', [
            'scripto_project_id' => $this->params('project-id'),
            'item_id' => $this->params('item-id'),
        ])->getContent();
        if (!$sItem) {
            return $this->redirect()->toRoute('admin/scripto-project');
        }
        $sMedia = $this->api()->searchOne('scripto_media', [
            'scripto_item_id' => $sItem->id(),
            'media_id' => $this->params('media-id'),
        ])->getContent();
        if (!$sMedia) {
            return $this->redirect()->toRoute('admin/scripto-project');
        }

        $sItem = $sMedia->scriptoItem();
        $view = new ViewModel;
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('sItem', $sItem);
        $view->setVariable('item', $sItem->item());
        return $view;
    }
}
