<?php
namespace Scripto\Controller\Admin;

use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

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
        $userInfo = $this->scripto()->apiClient()->queryUserInfo();
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
        return $this->scripto()->login('admin/scripto');
    }

    public function logoutAction()
    {
        return $this->scripto()->logout('admin/scripto');
    }
}
