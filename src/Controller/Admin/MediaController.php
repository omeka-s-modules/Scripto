<?php
namespace Scripto\Controller\Admin;

use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class MediaController extends AbstractActionController
{
    public function browseAction()
    {
        $project = $this->api()->read('scripto_projects', $this->params('project-id'))->getContent();
        $sItem = $this->api()->searchOne('scripto_items', ['item_id' => $this->params('item-id')])->getContent();

        $this->setBrowseDefaults('position');
        $query = array_merge(
            ['scripto_item_id' => $sItem->id()],
            $this->params()->fromQuery()
        );
        $response = $this->api()->search('scripto_media', $query);
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $sMedia = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('project', $project);
        $view->setVariable('sItem', $sItem);
        $view->setVariable('sMedia', $sMedia);
        return $view;
    }
}
