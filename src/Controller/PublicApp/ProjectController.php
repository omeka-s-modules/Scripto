<?php
namespace Scripto\Controller\PublicApp;

use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class ProjectController extends AbstractActionController
{
    public function browseAction()
    {
        $this->setBrowseDefaults('created');
        $response = $this->api()->search('scripto_projects', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $projects = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('projects', $projects);
        return $view;
    }

    public function guidelinesAction()
    {
        $project = $this->scripto()->getRepresentation($this->params('project-id'));
        if (!$project) {
            return $this->redirect()->toRoute('scripto');
        }

        $view = new ViewModel;
        $view->setVariable('project', $project);
        $this->layout()->setVariable('project', $project);
        return $view;
    }

    public function showAction()
    {
        return $this->redirect()->toRoute(
            'scripto-item',
            ['action' => 'browse'],
            ['query' => $this->params()->fromQuery()],
            true
        );
    }
}
