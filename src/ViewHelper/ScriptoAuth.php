<?php
namespace Scripto\ViewHelper;

use Scripto\Form\ScriptoLoginForm;
use Scripto\Form\ScriptoLogoutForm;
use Scripto\Mediawiki\ApiClient;
use Zend\Form\Element;
use Zend\Router\Http\RouteMatch;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper used to render Scripto admin interface breadcrumbs.
 */
class ScriptoAuth extends AbstractHelper
{
    /**
     * @var ApiClient
     */
    protected $client;

    /**
     * @var ServiceLocatorInterface
     */
    protected $formElementManager;

    /**
     * @param ApiClient $client
     * @param ServiceLocatorInterface $formElementManager
     */
    public function __construct(ApiClient $client, ServiceLocatorInterface $formElementManager)
    {
        $this->client = $client;
        $this->formElementManager = $formElementManager;
    }

    /**
     * Return the Scripto login and logout bar.
     *
     * @return string
     */
    public function loginBar()
    {
        $view = $this->getView();
        if ($this->client->userIsLoggedIn()) {
            $userInfo = $this->client->getUserInfo();
            $form = $this->formElementManager->get(ScriptoLogoutForm::class);
            $form->setAttribute('action', $view->url(
                'admin/scripto',
                ['action' => 'logout'],
                ['query' => ['redirect' => $this->getCurrentUrl()]]
            ));
            return sprintf(
                '<div id="scripto-login"><h3>%s</h3>%s</div>',
                sprintf($view->translate('Logged in to Scripto (%s)'),  $userInfo['name']),
                $view->form($form)
            );
        } else {
            $form = $this->formElementManager->get(ScriptoLoginForm::class);
            $form->setAttribute('action', $view->url(
                'admin/scripto',
                ['action' => 'login'],
                ['query' => ['redirect' => $this->getCurrentUrl()]]
            ));
            return sprintf(
                '<div id="scripto-login"><h3>%s</h3>%s</div>',
                $view->translate('Log in to Scripto'),
                $view->form($form)
            );
        }
    }

    /**
     * Get the current URL, including query string.
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        $view = $this->getView();
        return sprintf(
            '%s?%s',
            $view->url(null, [], true),
            http_build_query($view->params()->fromQuery())
        );
    }
}
