<?php
namespace Scripto\ControllerPlugin;

use Omeka\Api\Exception\NotFoundException;
use Scripto\Form\ScriptoLoginForm;
use Scripto\Form\ScriptoLogoutForm;
use Scripto\Mediawiki\ApiClient;
use Scripto\Mediawiki\Exception\ClientloginException;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Controller plugin used for Scripto-specific functionality.
 */
class Scripto extends AbstractPlugin
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

    /**
     * Return Scripto's MediaWiki API client.
     *
     * @return ApiClient
     */
    public function apiClient()
    {
        return $this->apiClient;
    }

    /**
     * Get a Scripto representation.
     *
     * Provides a single method to get a Scripto project, item, or media
     * representation. This is needed primarily becuase Scripto routes via Omeka
     * item and media IDs, not their corresponding Scripto item and media IDs.
     *
     * @return ScriptoProjectRepresentation|ScriptoItemRepresentation|ScriptoMediaRepresentation
     */
    public function getRepresentation($projectId, $itemId = null, $mediaId = null)
    {
        $controller = $this->getController();

        if (!$itemId && $mediaId) {
            // An item ID must accompany a media ID.
            return false;
        }

        try {
            $project = $controller->api()->read('scripto_projects', $projectId)->getContent();
        } catch (NotFoundException $e) {
            return false;
        }

        if (!$itemId && !$mediaId) {
            return $project;
        }

        $sItem = $controller->api()->searchOne('scripto_items', [
            'scripto_project_id' => $project->id(),
            'item_id' => $itemId,
        ])->getContent();

        if (!$sItem) {
            // The Scripto item does not exist.
            return false;
        }

        if (!$mediaId) {
            return $sItem;
        }

        $sMedia = $controller->api()->searchOne('scripto_media', [
            'scripto_item_id' => $sItem->id(),
            'media_id' => $mediaId,
        ])->getContent();

        if (!$sMedia) {
            // The Scripto media does not exist.
            return false;
        }

        return $sMedia;
    }

    /**
     * Prepare a MediaWiki list for rendering.
     *
     * @param array $list
     * @return array
     */
    public function prepareMediawikiList(array $list)
    {
        foreach ($list as $key => $row) {
            if (preg_match('/^(Talk:)?(\d+):(\d+):(\d+)$/', $row['title'], $matches)) {
                $sMedia = $this->getRepresentation($matches[2], $matches[3], $matches[4]);
                if ($sMedia) {
                    $list[$key]['scripto_media'] = $sMedia;
                }
            }
        }
        return $list;
    }

    /**
     * Cache MediaWiki pages.
     *
     * Leverages bulk caching in the API client so subsequent queries of
     * individual pages don't make API requests.
     *
     * @param array An array of Scripto media representations
     */
    public function cacheMediawikiPages(array $sMedia)
    {
        $titles = [];
        foreach ($sMedia as $sm) {
            $titles[] = $sm->pageTitle(0);
        }
        $this->apiClient()->queryPages($titles);
    }

    /**
     * Log in to Scripto (MediaWiki)
     *
     * @param string $name The redirect route name (for login without redirect)
     * @param array $params The redirect route parameters (for login without redirect)
     */
    public function login($name, array $params = [])
    {
        $controller = $this->getController();
        if ($controller->getRequest()->isPost()) {
            $form = $controller->getForm(ScriptoLoginForm::class);
            $form->setData($controller->getRequest()->getPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                try {
                    $this->apiClient()->login(
                        $formData['scripto-username'],
                        $formData['scripto-password']
                    );
                    $controller->messenger()->addSuccess($controller->translate('Successfully logged in to Scripto.'));
                } catch (ClientloginException $e) {
                    $controller->messenger()->addError($controller->translate('Cannot log in to Scripto. Email or password is invalid.'));
                }
            }
            $redirect = $controller->getRequest()->getQuery('redirect');
            if ($redirect) {
                return $controller->redirect()->toUrl($redirect);
            }
        }
        return $controller->redirect()->toRoute($name, $params);
    }

    /**
     * Log out of Scripto (MediaWiki)
     *
     * @param string $name The redirect route name (for logout without redirect)
     * @param array $params The redirect route parameters (for logout without redirect)
     */
    public function logout($name, array $params = [])
    {
        $controller = $this->getController();
        if ($controller->getRequest()->isPost()) {
            $form = $controller->getForm(ScriptoLogoutForm::class);
            $form->setData($controller->getRequest()->getPost());
            if ($form->isValid()) {
                $this->apiClient()->logout();
                $controller->messenger()->addSuccess($controller->translate('Successfully logged out of Scripto.'));
            }
            $redirect = $controller->getRequest()->getQuery('redirect');
            if ($redirect) {
                return $controller->redirect()->toUrl($redirect);
            }
        }
        return $controller->redirect()->toRoute($name, $params);
    }
}
