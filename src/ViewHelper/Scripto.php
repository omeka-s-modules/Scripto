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
 * View helper used to render Scripto template elements.
 */
class Scripto extends AbstractHelper
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
     * @var RouteMatch
     */
    protected $routeMatch;

    /**
     * @param ApiClient $client
     * @param ServiceLocatorInterface $formElementManager
     * @param RouteMatch $routeMatch
     */
    public function __construct(ApiClient $client, ServiceLocatorInterface $formElementManager, RouteMatch $routeMatch)
    {
        $this->client = $client;
        $this->formElementManager = $formElementManager;
        $this->routeMatch = $routeMatch;
    }

    /**
     * Return the Scripto login and logout bar.
     *
     * @return string
     */
    public function adminLoginBar()
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
                sprintf(
                    $view->translate('Logged in to Scripto as %s'),
                    $view->hyperlink($userInfo['name'], $view->url('admin/scripto-user-id', ['user-id' => $userInfo['name']]))
                ),
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
     * Render Scripto admin interface breadcrumbs
     *
     * @return string
     */
    public function adminBreadcrumbs()
    {
        $bc = [];
        $view = $this->getView();
        $routeName = $this->routeMatch->getMatchedRouteName();

        if ('admin/scripto' === $routeName) {
            $bc[] = $view->translate('Dashboard');
        } else {
            $bc[] = $view->hyperlink($view->translate('Dashboard'), $view->url('admin/scripto'));
        }

        if ('admin/scripto-project' === $routeName) {
            $bc[] = $view->translate('Project browse');
        } elseif (in_array($routeName, ['admin/scripto-item', 'admin/scripto-media', 'admin/scripto-media-id', 'admin/scripto-revision', 'admin/scripto-revision-id', 'admin/scripto-revision-compare'])) {
            $bc[] = $view->hyperlink(
                $view->translate('Project browse'),
                $view->url('admin/scripto-project')
            );
        }

        if ('admin/scripto-item' === $routeName) {
            $bc[] = $view->translate('Project review');
        } elseif (in_array($routeName, ['admin/scripto-media', 'admin/scripto-media-id', 'admin/scripto-revision', 'admin/scripto-revision-id', 'admin/scripto-revision-compare'])) {
            $bc[] = $view->hyperlink(
                $view->translate('Project review'),
                $view->url('admin/scripto-item', [
                    'action' => 'browse',
                    'project-id' => $this->routeMatch->getParam('project-id'),
                ])
            );
        }

        if ('admin/scripto-media' === $routeName) {
            $bc[] = $view->translate('Item review');
        } elseif (in_array($routeName, ['admin/scripto-media-id', 'admin/scripto-revision', 'admin/scripto-revision-id', 'admin/scripto-revision-compare'])) {
            $bc[] = $view->hyperlink(
                $view->translate('Item review'),
                $view->url('admin/scripto-media', [
                    'action' => 'browse',
                    'project-id' => $this->routeMatch->getParam('project-id'),
                    'item-id' => $this->routeMatch->getParam('item-id'),
                ])
            );
        }

        if ('admin/scripto-media-id' === $routeName) {
            $bc[] = $view->translate('Media review');
        } elseif (in_array($routeName, ['admin/scripto-revision', 'admin/scripto-revision-id', 'admin/scripto-revision-compare'])) {
            $bc[] = $view->hyperlink(
                $view->translate('Media review'),
                $view->url('admin/scripto-media-id', [
                    'action' => 'show',
                    'project-id' => $this->routeMatch->getParam('project-id'),
                    'item-id' => $this->routeMatch->getParam('item-id'),
                    'media-id' => $this->routeMatch->getParam('media-id'),
                ])
            );
        }

        if ('admin/scripto-revision' === $routeName) {
            $bc[] = $view->translate('Revision browse');
        } elseif (in_array($routeName, ['admin/scripto-revision-id', 'admin/scripto-revision-compare'])) {
            $bc[] = $view->hyperlink(
                $view->translate('Revision browse'),
                $view->url('admin/scripto-revision', [
                    'action' => 'browse',
                    'project-id' => $this->routeMatch->getParam('project-id'),
                    'item-id' => $this->routeMatch->getParam('item-id'),
                    'media-id' => $this->routeMatch->getParam('media-id'),
                ])
            );
        }

        if ('admin/scripto-revision-id' === $routeName) {
            $bc[] = $view->translate('Revision show');
        }

        if ('admin/scripto-revision-compare' === $routeName) {
            $bc[] = $view->translate('Revision compare');
        }

        return sprintf('<div class="breadcrumbs">%s</div>', implode('<div class="separator"></div>', $bc));
    }

    /**
     * Render Scripto MediaWiki pagination.
     *
     * @return string
     */
    public function adminMediawikiPagination()
    {
        $view = $this->getView();
        return sprintf(
            '<span class="mediawiki-pagination">%s | %s</span>',
            $view->hyperlink($view->translate('First page'), $view->url(null, [], true)),
            $view->continue ? $view->hyperlink(
                $view->translate('Next page'),
                $view->url(null, [], ['query' => ['continue' => $view->continue]], true)
            ) : $view->translate('Next page')
        );
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
