<?php
namespace Scripto\Controller\Admin;

use Zend\View\Model\ViewModel;

class UserController extends AbstractScriptoController
{
    public function browseAction()
    {
        $response = $this->scriptoApiClient()->queryAllUsers(100, $this->params()->fromQuery('continue'));
        $users = $response['query']['allusers'];
        $continue = isset($response['continue']) ? $response['continue']['aufrom'] : null;

        $view = new ViewModel;
        $view->setVariable('users', $users);
        $view->setVariable('continue', $continue);
        return $view;
    }

    public function showAction()
    {
        $userName = $this->params('user-id');
        $continue = $this->params()->fromQuery('continue');

        $user = $this->scriptoApiClient()->queryUser($userName);
        $response = $this->scriptoApiClient()->queryUserContributions($userName, 100, $continue);
        $userCons = $this->prepareUserContributions($response['query']['usercontribs']);
        $continue = isset($response['continue']) ? $response['continue']['uccontinue'] : null;

        $view = new ViewModel;
        $view->setVariable('user', $user);
        $view->setVariable('userCons', $userCons);
        $view->setVariable('continue', $continue);
        return $view;
    }
}
