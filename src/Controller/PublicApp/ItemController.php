<?php
namespace Scripto\Controller\PublicApp;

use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class ItemController extends AbstractActionController
{
    public function browseAction()
    {
        $project = $this->scripto()->getRepresentation($this->params('project-id'));
        if (!$project) {
            return $this->redirect()->toRoute('scripto');
        }

        $this->setBrowseDefaults('id');
        $query = $this->params()->fromQuery();
        $query['scripto_project_id'] = $this->params('project-id');
        if ($project->filterApproved()
            && !isset($query['is_approved'])
            && !isset($query['is_not_approved'])
            && !isset($query['is_in_progress'])
            && !isset($query['is_new'])
            && !isset($query['is_edited_after_imported'])
        ) {
            $query['is_not_approved'] = true;
        }
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
        return $this->redirect()->toRoute(
            'scripto-media',
            ['action' => 'browse'],
            ['query' => $this->params()->fromQuery()],
            true
        );
    }
}
