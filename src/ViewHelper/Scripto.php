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
    protected $apiClient;

    /**
     * @var ServiceLocatorInterface
     */
    protected $formElementManager;

    /**
     * @var RouteMatch
     */
    protected $routeMatch;

    /**
     * @var array Breadcrumbs route map
     */
    protected $bcRouteMap = [
        'admin/scripto' => [
            'breadcrumbs' => [],
            'text' => 'Dashboard', // @translate
            'params' => [],
        ],
        'admin/scripto-user' => [
            'breadcrumbs' => ['admin/scripto'],
            'text' => 'Users', // @translate
            'params' => [],
        ],
        'admin/scripto-user-contributions' => [
            'breadcrumbs' => ['admin/scripto', 'admin/scripto-user'],
            'text' => 'User contributions', // @translate
            'params' => ['user-id'],
        ],
        'admin/scripto-user-watchlist' => [
            'breadcrumbs' => ['admin/scripto', 'admin/scripto-user'],
            'text' => 'User watchlist', // @translate
            'params' => ['user-id'],
        ],
        'admin/scripto-project' => [
            'breadcrumbs' => ['admin/scripto'],
            'text' => 'Projects', // @translate
            'params' => [],
        ],
        'admin/scripto-item' => [
            'breadcrumbs' => ['admin/scripto', 'admin/scripto-project'],
            'text' => 'Review project', // @translate
            'params' => ['project-id'],
        ],
        'admin/scripto-media' => [
            'breadcrumbs' => ['admin/scripto', 'admin/scripto-project', 'admin/scripto-item'],
            'text' => 'Review item', // @translate
            'params' => ['project-id', 'item-id'],
        ],
        'admin/scripto-media-id' => [
            'breadcrumbs' => ['admin/scripto', 'admin/scripto-project', 'admin/scripto-item', 'admin/scripto-media'],
            'text' => 'Review media', // @translate
            'params' => ['project-id', 'item-id', 'media-id'],
        ],
        'admin/scripto-revision' => [
            'breadcrumbs' => ['admin/scripto', 'admin/scripto-project', 'admin/scripto-item', 'admin/scripto-media', 'admin/scripto-media-id'],
            'text' => 'Revisions', // @translate
            'params' => ['project-id', 'item-id', 'media-id'],
        ],
        'admin/scripto-revision-compare' => [
            'breadcrumbs' => ['admin/scripto', 'admin/scripto-project', 'admin/scripto-item', 'admin/scripto-media', 'admin/scripto-media-id', 'admin/scripto-revision'],
            'text' => 'Compare revisions', // @translate
            'params' => ['project-id', 'item-id', 'media-id', 'to-revision-id', 'from-revision-id'],
        ],
    ];

    /**
     * @param ApiClient $apiClient
     * @param ServiceLocatorInterface $formElementManager
     * @param RouteMatch $routeMatch
     */
    public function __construct(ApiClient $apiClient, ServiceLocatorInterface $formElementManager, RouteMatch $routeMatch)
    {
        $this->apiClient = $apiClient;
        $this->formElementManager = $formElementManager;
        $this->routeMatch = $routeMatch;
    }

    /**
     * Get the MediaWiki API client.
     *
     * @return ApiClient
     */
    public function apiClient()
    {
        return $this->apiClient;
    }

    /**
     * Return the Scripto login and logout bar.
     *
     * @return string
     */
    public function adminLoginBar()
    {
        $view = $this->getView();
        if ($this->apiClient->userIsLoggedIn()) {
            $routeName = $this->routeMatch->getMatchedRouteName();
            $userInfo = $this->apiClient->getUserInfo();
            $form = $this->formElementManager->get(ScriptoLogoutForm::class);
            $form->setAttribute('action', $view->url(
                'admin/scripto',
                ['action' => 'logout'],
                ['query' => ['redirect' => $this->getCurrentUrl()]]
            ));
            return sprintf(
                '<div id="scripto-login"><h3>%s | %s | %s | %s</h3>%s</div>',
                sprintf($view->translate('Logged in to Scripto as %s'), $userInfo['name']),
                'admin/scripto' === $routeName
                    ? $view->translate('Dashboard')
                    : $view->hyperlink($view->translate('Dashboard'), $view->url('admin/scripto')),
                'admin/scripto-user-contributions' === $routeName
                    ? $view->translate('Contributions')
                    : $view->hyperlink($view->translate('Contributions'), $view->url('admin/scripto-user-contributions', ['user-id' => $userInfo['name']])),
                'admin/scripto-user-watchlist' === $routeName
                    ? $view->translate('Watchlist')
                    : $view->hyperlink($view->translate('Watchlist'), $view->url('admin/scripto-user-watchlist', ['user-id' => $userInfo['name']])),
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
        if (!isset($this->bcRouteMap[$routeName])) {
            return;
        }
        foreach ($this->bcRouteMap[$routeName]['breadcrumbs'] as $bcRoute) {
            $params = [];
            foreach ($this->bcRouteMap[$bcRoute]['params'] as $bcParam) {
                $params[$bcParam] = $this->routeMatch->getParam($bcParam);
            }
            $bc[] = $view->hyperlink($this->bcRouteMap[$bcRoute]['text'], $view->url($bcRoute, $params));
        }
        $bc[] = $view->translate($this->bcRouteMap[$routeName]['text']);
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
            '<nav class="pagination" role="navigation">%s%s</nav>',
            $view->hyperlink('', $view->url(null, [], true), [
                'class' => 'first o-icon-first button', 
                'title' => $view->translate('First page'), 
                'aria-label' => $view->translate('First page')
            ]),
            $view->continue
                ? $view->hyperlink('', $view->url(null, [], ['query' => ['continue' => $view->continue]], true), [
                    'class' => 'next o-icon-next button', 
                    'title' => $view->translate('Next page'), 
                    'aria-label' => $view->translate('Next page')
                ])
                : '<span class="next o-icon-next button inactive"></span>'
        );
    }

    /**
     * Render an admin search box for filtering items and media.
     *
     * @return string
     */
    public function adminSearchBox()
    {
        $view = $this->getView();
        return sprintf(
            '<form id="scripto-search"><input type="text" name="search" value="%s" aria-label="%s"><button type="submit" aria-label="%s" class="o-icon-search"></button></form>',
            $view->escapeHtml($view->params()->fromQuery('search')),
            $view->translate('Query'),
            $view->translate('Search')
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
