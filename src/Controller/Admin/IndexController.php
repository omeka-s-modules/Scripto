<?php
namespace Scripto\Controller\Admin;

use Scripto\Form\ScriptoLoginForm;
use Scripto\Form\ScriptoLogoutForm;
use Scripto\Mediawiki\ApiClient;
use Scripto\Mediawiki\Exception\ClientloginException;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractScriptoController
{
    /**
     * @var ApiClient
     */
    protected $apiClient;

    /**
     * @param ApiClient $apiClient
     */
    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function loginAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ScriptoLoginForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                try {
                    $this->apiClient->login(
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
                $this->apiClient->logout();
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
