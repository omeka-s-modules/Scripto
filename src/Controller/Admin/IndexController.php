<?php
namespace Scripto\Controller\Admin;

use Scripto\Form\ScriptoLoginForm;
use Scripto\Form\ScriptoLogoutForm;
use Scripto\Mediawiki\Exception\ClientloginException;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractScriptoController
{
    public function loginAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ScriptoLoginForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                try {
                    $this->scriptoApiClient()->login(
                        $formData['scripto-username'],
                        $formData['scripto-password']
                    );
                    $this->messenger()->addSuccess($this->translate('Successfully logged in to Scripto.'));
                } catch (ClientloginException $e) {
                    $this->messenger()->addError($this->translate('Cannot log in to Scripto. Email or password is invalid.'));
                }
            }
            $redirect = $this->getRequest()->getQuery('redirect');
            if ($redirect) {
                return $this->redirect()->toUrl($redirect);
            }
        }
        return $this->redirect()->toRoute('admin/scripto-project', ['action' => 'browse']);
    }

    public function logoutAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ScriptoLogoutForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $this->scriptoApiClient()->logout();
                $this->messenger()->addSuccess($this->translate('Successfully logged out of Scripto.'));
            }
            $redirect = $this->getRequest()->getQuery('redirect');
            if ($redirect) {
                return $this->redirect()->toUrl($redirect);
            }
        }
        return $this->redirect()->toRoute('admin/scripto-project', ['action' => 'browse']);
    }
}
