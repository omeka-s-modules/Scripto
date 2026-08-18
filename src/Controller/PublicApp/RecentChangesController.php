<?php
namespace Scripto\Controller\PublicApp;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class RecentChangesController extends AbstractActionController
{
    public function browseAction()
    {
        $hours = $this->params()->fromQuery('hours', 720); // 30 days
        $continue = $this->params()->fromQuery('continue');

        $response = $this->scripto()->apiClient()->queryRecentChanges($hours, 10, $continue);

        $recentChanges = $this->scripto()->prepareMediawikiList($response['query']['recentchanges']);

        $continue = isset($response['continue']) ? $response['continue']['rccontinue'] : null;

        $view = new ViewModel;
        $view->setVariable('recentChanges', $recentChanges);
        $view->setVariable('hours', $hours);
        $view->setVariable('continue', $continue);
        return $view;
    }
}
