<?php
namespace Scripto\Controller\Admin;

use Scripto\Form\ScriptoLoginForm;
use Scripto\Form\ScriptoLogoutForm;
use Scripto\Mediawiki\Exception\ClientloginException;
use Zend\Authentication\AuthenticationService;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    /**
     * @var AuthenticationService
     */
    protected $auth;

    /**
     * @param AuthenticationService $auth
     */
    public function __construct(AuthenticationService $auth)
    {
        $this->auth = $auth;
    }

    public function indexAction()
    {
        $userInfo = $this->scripto()->apiClient()->getUserInfo();
        $user = $this->scripto()->apiClient()->queryUser($userInfo['name']);

        $response = $this->scripto()->apiClient()->queryUserContributions($userInfo['name'], 10);
        $userCons = $this->scripto()->prepareMediawikiList($response['query']['usercontribs']);

        if ($this->scripto()->apiClient()->userIsLoggedIn()) {
            $response = $this->scripto()->apiClient()->queryWatchlist(720, 20); // 30 days
            $watchlist = $this->scripto()->prepareMediawikiList($response['query']['watchlist']);
        } else {
            $watchlist = [];
        }

        $projects = $this->api()->search('scripto_projects', [
            'sort_by' => 'created',
            'sort_order' => 'desc',
            'owner_id' => $this->auth->getIdentity()->getId(),
        ])->getContent();

        $reviewingProjects = $this->api()->search('scripto_projects', [
            'sort_by' => 'created',
            'sort_order' => 'desc',
            'has_reviewer_id' => $this->auth->getIdentity()->getId(),
        ])->getContent();

        $view = new ViewModel;
        $view->setVariable('user', $user);
        $view->setVariable('userCons', $userCons);
        $view->setVariable('watchlist', $watchlist);
        $view->setVariable('projects', $projects);
        $view->setVariable('reviewingProjects', $reviewingProjects);
        return $view;
    }

    public function loginAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ScriptoLoginForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                try {
                    $this->scripto()->apiClient()->login(
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
                $this->scripto()->apiClient()->logout();
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
