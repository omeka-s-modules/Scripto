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

    public function contributionsAction()
    {
        $userName = $this->params('user-id');
        $continue = $this->params()->fromQuery('continue');

        $user = $this->scriptoApiClient()->queryUser($userName);
        $response = $this->scriptoApiClient()->queryUserContributions($userName, 100, $continue);
        $userCons = $this->prepareMediawikiList($response['query']['usercontribs']);
        $continue = isset($response['continue']) ? $response['continue']['uccontinue'] : null;

        $view = new ViewModel;
        $view->setVariable('user', $user);
        $view->setVariable('userCons', $userCons);
        $view->setVariable('continue', $continue);
        return $view;
    }

    public function watchlistAction()
    {
        if (!$this->scriptoApiClient()->userIsLoggedIn()) {
            // User must be logged in.
            return $this->redirect()->toRoute('admin/scripto');
        }
        $userName = $this->params('user-id');
        $currentUser = $this->scriptoApiClient()->getUserInfo();
        if ($userName !== $currentUser['name']) {
            // Logged in user must be current user.
            return $this->redirect()->toRoute('admin/scripto-user-id', ['user-id' => $currentUser['name'], 'action' => 'watchlist']);
        }

        $hours = $this->params()->fromQuery('hours', 72); // 3 days
        $continue = $this->params()->fromQuery('continue');

        $response = $this->scriptoApiClient()->queryWatchlist($hours, 10, $continue);
        $watchlist = $this->prepareMediawikiList($response['query']['watchlist']);
        $continue = isset($response['continue']) ? $response['continue']['wlcontinue'] : null;

        $view = new ViewModel;
        $view->setVariable('user', $this->scriptoApiClient()->queryUser($userName));
        $view->setVariable('watchlist', $watchlist);
        $view->setVariable('hours', $hours);
        $view->setVariable('continue', $continue);
        return $view;
    }
}
