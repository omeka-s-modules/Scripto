<?php
namespace Scripto\Controller\Admin;

use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class ItemController extends AbstractActionController
{
    public function browseAction()
    {
        $project = $this->api()->read('scripto_projects', $this->params('project-id'))->getContent();

        $this->setBrowseDefaults('synced');
        $query = array_merge(
            ['scripto_project_id' => $this->params('project-id')],
            $this->params()->fromQuery()
        );
        $response = $this->api()->search('scripto_items', $query);
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $sItems = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('project', $project);
        $view->setVariable('sItems', $sItems);
        return $view;
    }

    public function showDetailsAction()
    {
        $sItem = $this->api()->searchOne('scripto_items', [
            'scripto_project_id' => $this->params('project-id'),
            'item_id' => $this->params('item-id'),
        ])->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('sItem', $sItem);
        $view->setVariable('item', $sItem->item());
        return $view;
    }

    public function showAction()
    {
        $project = $this->api()->read('scripto_projects', $this->params('project-id'))->getContent();
        $sItem = $this->api()->searchOne('scripto_items', [
            'scripto_project_id' => $this->params('project-id'),
            'item_id' => $this->params('item-id'),
        ])->getContent();


        $view = new ViewModel;
        $view->setVariable('project', $project);
        $view->setVariable('sItem', $sItem);
        $view->setVariable('item', $sItem->item());
        return $view;
    }
}
