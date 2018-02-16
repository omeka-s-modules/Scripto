<?php
namespace Scripto\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Scripto\Form\ScriptoProjectForm;
use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class ProjectController extends AbstractActionController
{
    public function addAction()
    {
        $form = $this->getForm(ScriptoProjectForm::class);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $formData['o:item_set'] = ['o:id' => $formData['o:item_set']];
                $formData['o:property'] = ['o:id' => $formData['o:property']];
                $response = $this->api($form)->create('scripto_projects', $formData);
                if ($response) {
                    $this->messenger()->addSuccess('Scripto project successfully created.'); // @translate
                    return $this->redirect()->toUrl($response->getContent()->adminUrl());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function editAction()
    {
        $form = $this->getForm(ScriptoProjectForm::class);
        $project = $this->api()->read('scripto_projects', $this->params('project-id'))->getContent();

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $formData['o:item_set'] = ['o:id' => $formData['o:item_set']];
                $formData['o:property'] = ['o:id' => $formData['o:property']];
                $response = $this->api($form)->update('scripto_projects', $this->params('project-id'), $formData);
                if ($response) {
                    $this->messenger()->addSuccess('Scripto project successfully edited.'); // @translate
                    return $this->redirect()->toUrl($response->getContent()->adminUrl());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        } else {
            $data = $project->jsonSerialize();
            $data['o:item_set'] = $data['o:item_set']->id();
            $data['o:property'] = $data['o:property']->id();
            $form->setData($data);
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('project', $project);
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('scripto_projects', $this->params('project-id'));
                if ($response) {
                    $this->messenger()->addSuccess('Scripto project successfully deleted'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    public function browseAction()
    {
        $this->setBrowseDefaults('created');
        $response = $this->api()->search('scripto_projects', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $view->setVariable('projects', $response->getContent());
        return $view;
    }

    public function showDetailsAction()
    {
        $response = $this->api()->read('scripto_projects', $this->params('project-id'));

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('project', $response->getContent());
        return $view;
    }

    public function showAction()
    {
        $project = $this->api()->read('scripto_projects', $this->params('project-id'))->getContent();

        $view = new ViewModel;
        $view->setVariable('project', $project);
        return $view;
    }

    public function browseItemsAction()
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
}
