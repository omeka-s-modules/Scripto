<?php
namespace Scripto\Controller\PublicApp;

use Scripto\Form\CreateAccountForm;
use Scripto\Mediawiki\Exception\CreateaccountException;
use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
    }

    public function createAccountAction()
    {
        $form = $this->getForm(CreateAccountForm::class);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                try {
                    $this->scripto()->apiClient()->createAccount(
                        $formData['username'], $formData['password'], $formData['retype'],
                        $formData['email'], $formData['realname']
                    );
                    $this->messenger()->addSuccess('Scripto account successfully created.'); // @translate
                    return $this->redirect()->toRoute('scripto');
                } catch (CreateaccountException $e) {
                    $this->messenger()->addError($e->getMessage());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function loginAction()
    {
        return $this->scripto()->login('scripto');
    }

    public function logoutAction()
    {
        return $this->scripto()->logout('scripto');
    }
}
