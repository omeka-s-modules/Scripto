<?php
namespace Scripto\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Omeka\Stdlib\HtmlPurifier;
use Omeka\Stdlib\Message;
use Scripto\Form\ProjectForm;
use Scripto\Form\ProjectImportForm;
use Scripto\Form\ProjectUnimportForm;
use Scripto\Form\ProjectSyncForm;
use Scripto\Job\ImportProject;
use Scripto\Job\SyncProject;
use Scripto\Job\UnimportProject;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class ProjectController extends AbstractActionController
{
    /**
     * @var HtmlPurifier
     */
    protected $htmlPurifier;

    public function __construct(HtmlPurifier $htmlPurifier)
    {
        $this->htmlPurifier = $htmlPurifier;
    }

    public function addAction()
    {
        $form = $this->getForm(ProjectForm::class);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $formData['o:is_public'] = $this->params()->fromPost('o:is_public');
                $formData['o:item_set'] = ['o:id' => $formData['o:item_set']];
                $formData['o:property'] = ['o:id' => $formData['o:property']];
                $formData['o-module-scripto:guidelines'] = $this->htmlPurifier->purify($formData['o-module-scripto:guidelines']);
                $formData['o-module-scripto:create_account_text'] = $this->htmlPurifier->purify($formData['o-module-scripto:create_account_text']);
                $response = $this->api($form)->create('scripto_projects', $formData);
                if ($response) {
                    $this->messenger()->addSuccess('Scripto project successfully created.'); // @translate
                    return $this->redirect()->toUrl($response->getContent()->url());
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
        $form = $this->getForm(ProjectForm::class);
        $project = $this->scripto()->getRepresentation($this->params('project-id'));
        if (!$project) {
            return $this->redirect()->toRoute('admin/scripto');
        }

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $formData['o:is_public'] = $this->params()->fromPost('o:is_public');
                $formData['o:item_set'] = ['o:id' => $formData['o:item_set']];
                $formData['o:property'] = ['o:id' => $formData['o:property']];
                $formData['o-module-scripto:guidelines'] = $this->htmlPurifier->purify($formData['o-module-scripto:guidelines']);
                $formData['o-module-scripto:create_account_text'] = $this->htmlPurifier->purify($formData['o-module-scripto:create_account_text']);
                $formData['o-module-scripto:reviewer'] = $this->params()->fromPost('o-module-scripto:reviewer');
                $response = $this->api($form)->update('scripto_projects', $this->params('project-id'), $formData);
                if ($response) {
                    $this->messenger()->addSuccess('Scripto project successfully edited.'); // @translate
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        } else {
            $data = $project->jsonSerialize();
            $data['o:item_set'] = $data['o:item_set'] ? $data['o:item_set']->id() : null;
            $data['o:property'] = $data['o:property'] ? $data['o:property']->id() : null;
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
        $project = $this->scripto()->getRepresentation($this->params('project-id'));
        if (!$project) {
            exit;
        }

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('project', $project);
        return $view;
    }

    public function showAction()
    {
        return $this->redirect()->toRoute('admin/scripto-item', ['action' => 'browse'], true);
    }

    public function syncAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ProjectSyncForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $job = $this->jobDispatcher()->dispatch(
                    SyncProject::class,
                    ['scripto_project_id' => $this->params('project-id')]
                );
                $message = new Message(
                    'Syncing Scripto project. This may take a while. %s', // @translate
                    sprintf(
                        '<a href="%s">%s</a>',
                        htmlspecialchars($this->url()->fromRoute('admin/id', ['controller' => 'job', 'id' => $job->getId()])),
                        $this->translate('See this job for sync progress.')
                    ));
                $message->setEscapeHtml(false);
                $this->messenger()->addSuccess($message);
                return $this->redirect()->toRoute('admin/scripto-item', ['action' => 'browse'], true);
            }
        }
        return $this->redirect()->toRoute('admin/scripto-project', ['action' => 'browse'], true);
    }

    public function importAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ProjectImportForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $job = $this->jobDispatcher()->dispatch(
                    ImportProject::class,
                    ['scripto_project_id' => $this->params('project-id')]
                );
                $message = new Message(
                    'Importing Scripto project text. This may take a while. %s', // @translate
                    sprintf(
                        '<a href="%s">%s</a>',
                        htmlspecialchars($this->url()->fromRoute('admin/id', ['controller' => 'job', 'id' => $job->getId()])),
                        $this->translate('See this job for import progress.')
                    ));
                $message->setEscapeHtml(false);
                $this->messenger()->addSuccess($message);
                return $this->redirect()->toRoute('admin/scripto-item', ['action' => 'browse'], true);
            }
        }
        return $this->redirect()->toRoute('admin/scripto-project', ['action' => 'browse'], true);
    }

    public function unimportAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ProjectUnimportForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $job = $this->jobDispatcher()->dispatch(
                    UnimportProject::class,
                    ['scripto_project_id' => $this->params('project-id')]
                );
                $message = new Message(
                    'Unimporting Scripto project text. This may take a while. %s', // @translate
                    sprintf(
                        '<a href="%s">%s</a>',
                        htmlspecialchars($this->url()->fromRoute('admin/id', ['controller' => 'job', 'id' => $job->getId()])),
                        $this->translate('See this job for unimport progress.')
                    ));
                $message->setEscapeHtml(false);
                $this->messenger()->addSuccess($message);
                return $this->redirect()->toRoute('admin/scripto-item', ['action' => 'browse'], true);
            }
        }
        return $this->redirect()->toRoute('admin/scripto-project', ['action' => 'browse'], true);
    }
}
