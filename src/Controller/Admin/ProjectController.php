<?php
namespace Scripto\Controller\Admin;

use Scripto\Form\ScriptoProjectForm;
use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class ProjectController extends AbstractActionController
{
    public function browseAction()
    {
        exit('Project::browse');
    }

    public function addAction()
    {
        $form = $this->getForm(ScriptoProjectForm::class, [
            'foo' => 'bar',
        ]);
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
        exit('Project::edit');
    }

    public function reviewAction()
    {
        exit('Project::review');
    }
}
