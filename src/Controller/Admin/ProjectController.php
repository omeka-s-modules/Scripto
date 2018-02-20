<?php
namespace Scripto\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Scripto\Form\ImportProjectForm;
use Scripto\Form\ScriptoProjectForm;
use Scripto\Form\SyncProjectForm;
use Scripto\Form\UnimportProjectForm;
use Scripto\Job\ImportProject;
use Scripto\Job\SyncProject;
use Scripto\Job\UnimportProject;
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
        $projects = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('projects', $projects);
        return $view;
    }

    public function showDetailsAction()
    {
        $project = $this->api()->read('scripto_projects', $this->params('project-id'))->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('project', $project);
        return $view;
    }

    public function showAction()
    {
        $project = $this->api()->read('scripto_projects', $this->params('project-id'))->getContent();

        $view = new ViewModel;
        $view->setVariable('project', $project);
        return $view;
    }

    public function showActionsAction()
    {
        $project = $this->api()->read('scripto_projects', $this->params('project-id'))->getContent();

        $view = new ViewModel;
        $view->setVariable('project', $project);
        $view->setVariable('syncForm', $this->getForm(SyncProjectForm::class));
        $view->setVariable('importForm', $this->getForm(ImportProjectForm::class));
        $view->setVariable('unimportForm', $this->getForm(UnimportProjectForm::class));
        return $view;
    }

    public function syncAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(SyncProjectForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $this->jobDispatcher()->dispatch(
                    SyncProject::class,
                    ['scripto_project_id' => $this->params('project-id')]
                );
                $this->messenger()->addSuccess('Syncing Scripto project. This may take a while.'); // @translate
                return $this->redirect()->toRoute(null, ['action' => 'show'], true);
            }
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    public function importAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ImportProjectForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $this->jobDispatcher()->dispatch(
                    ImportProject::class,
                    ['scripto_project_id' => $this->params('project-id')]
                );
                $this->messenger()->addSuccess('Importing Scripto project text. This may take a while.'); // @translate
                return $this->redirect()->toRoute(null, ['action' => 'show'], true);
            }
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    public function unimportAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(UnimportProjectForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $this->jobDispatcher()->dispatch(
                    UnimportProject::class,
                    ['scripto_project_id' => $this->params('project-id')]
                );
                $this->messenger()->addSuccess('Unimporting Scripto project text. This may take a while.'); // @translate
                return $this->redirect()->toRoute(null, ['action' => 'show'], true);
            }
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }
}
