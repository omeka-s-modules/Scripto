<?php
namespace Scripto\Controller\PublicApp;

use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class ItemController extends AbstractActionController
{
    public function browseAction()
    {
        $project = $this->scripto()->getRepresentation($this->params('project-id'));
        if (!$project) {
            return $this->redirect()->toRoute('scripto');
        }

        $this->setBrowseDefaults(null);
        $query = array_merge(
            ['scripto_project_id' => $this->params('project-id')],
            $this->params()->fromQuery()
        );
        $response = $this->api()->search('scripto_items', $query);
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $sItems = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('sItems', $sItems);
        $view->setVariable('project', $project);
        $this->layout()->setVariable('project', $project);
        return $view;
    }

    public function showAction()
    {
        return $this->redirect()->toRoute('scripto-media', ['action' => 'browse'], true);
    }
}
