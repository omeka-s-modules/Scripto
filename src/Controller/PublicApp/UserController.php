<?php
namespace Scripto\Controller\PublicApp;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class UserController extends AbstractActionController
{
    public function contributionsAction()
    {
        $userName = $this->params('user-id');
        $continue = $this->params()->fromQuery('continue');

        $user = $this->scripto()->apiClient()->queryUser($userName);
        $response = $this->scripto()->apiClient()->queryUserContributions($userName, 100, $continue);
        $userCons = $this->scripto()->prepareMediawikiList($response['query']['usercontribs']);
        $continue = isset($response['continue']) ? $response['continue']['uccontinue'] : null;

        $view = new ViewModel;
        $view->setVariable('user', $user);
        $view->setVariable('userCons', $userCons);
        $view->setVariable('continue', $continue);
        $project = $this->scripto()->getRepresentation($this->params('site-project-id'));
        if ($project) {
            $view->setVariable('project', $project);
            $this->layout()->setVariable('project', $project);
        }
        return $view;
    }

    public function watchlistAction()
    {
        if (!$this->scripto()->apiClient()->userIsLoggedIn()) {
            // User must be logged in.
            return $this->redirect()->toRoute('scripto');
        }

        $userName = $this->params('user-id');
        $currentUser = $this->scripto()->apiClient()->queryUserInfo();
        if ($userName !== $currentUser['name']) {
            // Logged in user must be current user.
            return $this->redirect()->toRoute('scripto-user-watchlist', ['user-id' => $currentUser['name']]);
        }

        $hours = $this->params()->fromQuery('hours', 720); // 30 days
        $continue = $this->params()->fromQuery('continue');

        $response = $this->scripto()->apiClient()->queryWatchlist($hours, 100, $continue);
        $watchlist = $this->scripto()->prepareMediawikiList($response['query']['watchlist']);
        $continue = isset($response['continue']) ? $response['continue']['wlcontinue'] : null;

        $view = new ViewModel;
        $view->setVariable('user', $this->scripto()->apiClient()->queryUser($userName));
        $view->setVariable('watchlist', $watchlist);
        $view->setVariable('hours', $hours);
        $view->setVariable('continue', $continue);
        $project = $this->scripto()->getRepresentation($this->params('site-project-id'));
        if ($project) {
            $view->setVariable('project', $project);
            $this->layout()->setVariable('project', $project);
        }
        return $view;
    }
}
