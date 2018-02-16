<?php
namespace Scripto\Controller\Admin;

use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class ItemController extends AbstractActionController
{
    public function showDetailsAction()
    {
        $response = $this->api()->searchOne('scripto_items', [
            'scripto_project_id' => $this->params('project-id'),
            'item_id' => $this->params('item-id'),
        ]);

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('sItem', $response->getContent());
        return $view;
    }

    public function reviewAction()
    {
        exit('Item::review');
    }
}
