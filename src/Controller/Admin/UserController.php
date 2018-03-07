<?php
namespace Scripto\Controller\Admin;

use Zend\View\Model\ViewModel;

class UserController extends AbstractScriptoController
{
    public function showAction()
    {
        $userName = $this->params('user-id');
        $continue = $this->params()->fromQuery('continue');

        $user = $this->scriptoApiClient()->queryUser($userName);
        $response = $this->scriptoApiClient()->queryUserContributions($userName, 100, $continue);

        $userCons = $response['query']['usercontribs'];
        foreach ($userCons as $key => $userCon) {
            if (preg_match('/^\d+:\d+:\d+$/', $userCon['title'])) {
                list($projectId, $itemId, $mediaId) = explode(':', $userCon['title']);
                $sMedia = $this->getScriptoRepresentation($projectId, $itemId, $mediaId);
                if ($sMedia) {
                    $userCons[$key]['scripto_project'] = $sMedia->scriptoItem()->scriptoProject();
                    $userCons[$key]['scripto_revision_url'] = $this->url()->fromRoute('admin/scripto-revision-id', [
                        'project-id' => $projectId,
                        'item-id' => $itemId,
                        'media-id' => $mediaId,
                        'revision-id' => $userCon['revid'],
                    ]);
                }
            }
        }

        $continue = isset($response['continue']) ? $response['continue']['uccontinue'] : null;

        $view = new ViewModel;
        $view->setVariable('user', $user);
        $view->setVariable('userCons', $userCons);
        $view->setVariable('continue', $continue);
        return $view;
    }
}
