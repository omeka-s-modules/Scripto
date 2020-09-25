<?php
namespace Scripto\Controller\PublicApp;

use Scripto\Form\CreateAccountForm;
use Scripto\Mediawiki\Exception\CreateaccountException;
use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $userInfo = $this->scripto()->apiClient()->queryUserInfo();
        $user = $this->scripto()->apiClient()->queryUser($userInfo['name']);

        $userCons = [];
        $watchlist = [];
        if ($this->scripto()->apiClient()->userIsLoggedIn()) {
            $response = $this->scripto()->apiClient()->queryUserContributions($userInfo['name'], 10);
            $userCons = $this->scripto()->prepareMediawikiList($response['query']['usercontribs']);

            $response = $this->scripto()->apiClient()->queryWatchlist(720, 20); // 30 days
            $watchlist = $this->scripto()->prepareMediawikiList($response['query']['watchlist']);
        }

        $view = new ViewModel;
        $view->setVariable('user', $user);
        $view->setVariable('userCons', $userCons);
        $view->setVariable('watchlist', $watchlist);
        $project = $this->scripto()->getRepresentation($this->params('site-project-id'));
        if ($project) {
            $view->setVariable('project', $project);
            $this->layout()->setVariable('project', $project);
        }
        return $view;
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
                    $this->messenger()->addSuccess('Your Scripto account has been created! Please check your email for a link to activate your account.'); // @translate
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
        $project = $this->scripto()->getRepresentation($this->params('site-project-id'));
        if ($project) {
            $view->setVariable('project', $project);
            $this->layout()->setVariable('project', $project);
        }
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
