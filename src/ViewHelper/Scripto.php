<?php
namespace Scripto\ViewHelper;

use Scripto\Form\ScriptoLoginForm;
use Scripto\Form\ScriptoLogoutForm;
use Scripto\Mediawiki\ApiClient;
use Laminas\Form\Element;
use Laminas\Router\Http\RouteMatch;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Helper\AbstractHelper;

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
     * @var SiteRepresentation Site cache for public application site context
     */
    protected $publicAppSite;

    /**
     * @var ScriptoProjectRepresentation Project cache for public application site context
     */
    protected $publicAppProject;

    /**
     * @var string This page's title for the public application
     */
    protected $postTitle;

    /**
     * @var string This page's subtitle for the public application
     */
    protected $postSubtitle;

    /**
     * @var array Admin breadcrumbs route map
     */
    protected $bcRouteMap;

    /**
     * @var array Map between item|media|content type strings for translation
     */
    protected $typeStringMap;

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
        $this->bcRouteMap = include 'breadcrumbs_route_map.php';
        $this->typeStringMap = include 'type_string_map.php';
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
     * Prepare the site context of the public application.
     */
    public function prepareSiteContext()
    {
        $view = $this->getView();
        $siteSlug = $this->routeMatch->getParam('site-slug');
        $siteProjectId = $this->routeMatch->getParam('site-project-id');
        if ($siteSlug) {
            $this->publicAppSite = $view->api()->read('sites', ['slug' => $siteSlug])->getContent();
            $this->publicAppProject = $view->api()->read('scripto_projects', $siteProjectId)->getContent();
        }
    }

    /**
     * Get the title of the public application.
     *
     * @return string
     */
    public function publicAppTitle()
    {
        $view = $this->getView();
        $title = $this->publicAppProject
            ? $this->publicAppProject->title()
            : $view->setting('installation_title', 'Omeka S');
        return sprintf('%s · %s', $view->translate('Scripto'), $title);
    }

    /**
     * Get the project link for the public application navigation.
     *
     * @return string
     */
    public function publicAppProjectLink()
    {
        $view = $this->getView();
        return $this->publicAppProject
            ? $this->publicAppProject->link($view->translate('Project'), null, ['class' => 'page-link'])
            : $view->hyperlink(
                $view->translate('Projects'),
                $view->url('scripto-project', ['action' => 'browse'], true),
                ['class' => 'page-link']
            );
    }

    /**
     * Get the site link for the public application navigation.
     *
     * @return string
     */
    public function publicAppSiteLink()
    {
        $view = $this->getView();
        if ($this->publicAppSite) {
            return $view->hyperlink($this->publicAppSite->title(), $this->publicAppSite->siteUrl(), ['class' => 'page-link']);
        }
    }

    /**
     * Get the URL to the public application stylesheet.
     *
     * @return string
     */
    public function publicAppStylesheet()
    {
        $view = $this->getView();
        $defaultStylesheet = $view->assetUrl('css/public-app.css', 'Scripto');

        if (!$this->publicAppSite) {
            return $defaultStylesheet;
        }

        // Get the Scripto stylesheets.
        $stylesheets = [];
        try {
            $path = sprintf('%s/modules/Scripto/asset/css/site-themes', OMEKA_PATH);
            foreach (new \DirectoryIterator($path) as $fileinfo) {
                if ($fileinfo->isFile() && $fileinfo->isReadable() && 'css' === $fileinfo->getExtension()) {
                    $stylesheets[] = $fileinfo->getBasename('.css');
                }
            }
        } catch (\UnexpectedValueException $e) {
            return $defaultStylesheet; // path not found
        }

        if (in_array($this->publicAppSite->theme(), $stylesheets)) {
            // Use the site's corresponding Scripto stylesheet.
            return $view->assetUrl(sprintf('css/site-themes/%s.css', $this->publicAppSite->theme()), 'Scripto');
        }
        return $defaultStylesheet;
    }

    /**
     * Set and get this page's post title for the public application.
     *
     * @param string $postTitle
     * @return string
     */
    public function postTitle($postTitle = null)
    {
        if (isset($postTitle)) {
            $this->postTitle = $postTitle;
        } else {
            return $this->postTitle;
        }
    }

    /**
     * Set and get this page's post subtitle for the public application.
     *
     * @param string $postSubtitle
     * @return string
     */
    public function postSubtitle($postSubtitle = null)
    {
        if (isset($postSubtitle)) {
            $this->postSubtitle = $postSubtitle;
        } else {
            return $this->postSubtitle;
        }
    }

    /**
     * Return the admin Scripto login and logout bar.
     *
     * @return string
     */
    public function adminLoginBar()
    {
        $view = $this->getView();
        if ($this->apiClient->userIsLoggedIn()) {
            $routeName = $this->routeMatch->getMatchedRouteName();
            $userInfo = $this->apiClient->queryUserInfo();
            $form = $this->formElementManager->get(ScriptoLogoutForm::class);
            $form->setAttribute('action', $view->url(
                'admin/scripto',
                ['action' => 'logout'],
                ['query' => ['redirect' => $this->getCurrentUrl()]]
            ));
            return sprintf(
                '<div id="scripto-login"><h3>%s | %s | %s | %s</h3>%s</div>',
                sprintf($view->translate('Logged in to Scripto as %s'), $userInfo['name']), // @translate
                'admin/scripto' === $routeName
                    ? $view->translate('Dashboard') // @translate
                    : $view->hyperlink($view->translate('Dashboard'), $view->url('admin/scripto')),
                'admin/scripto-user-contributions' === $routeName
                    ? $view->translate('Contributions') // @translate
                    : $view->hyperlink($view->translate('Contributions'), $view->url('admin/scripto-user-contributions', ['user-id' => $userInfo['name']])),
                'admin/scripto-user-watchlist' === $routeName
                    ? $view->translate('Watchlist') // @translate
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
                $view->translate('Log in to Scripto'), // @translate
                $view->form($form)
            );
        }
    }

    /**
     * Return the public Scripto login and logout bar.
     *
     * @return string
     */
    public function loginBar()
    {
        $view = $this->getView();
        if ($this->apiClient->userIsLoggedIn()) {
            $userInfo = $this->apiClient->queryUserInfo();
            $form = $this->formElementManager->get(ScriptoLogoutForm::class);
            $form->setAttribute('action', $view->url(
                'scripto',
                ['action' => 'logout'],
                ['query' => ['redirect' => $this->getCurrentUrl()]]
            ));
            return sprintf(
                '<div class="user logged-in">
                    <a href="#" class="user-toggle page-link menu-toggle" aria-label="%s"></a>
                    <ul class="user-menu">
                        <li>%s</li>
                        <li>%s</li>
                        <li>%s</li>
                        <li>%s</li>
                    </ul>
                </div>
                %s',
                $view->translate('User menu'), // @translate
                sprintf($view->translate('Logged in to Scripto as %s'), sprintf('<span class="username">%s</span>', $userInfo['name'])),
                $view->hyperlink($view->translate('Dashboard'), $view->url('scripto', ['action' => 'index'], true)),
                $view->hyperlink($view->translate('Contributions'), $view->url('scripto-user-contributions', ['action' => 'contributions', 'user-id' => $userInfo['name']], true)),
                $view->hyperlink($view->translate('Watchlist'), $view->url('scripto-user-watchlist', ['action' => 'watchlist', 'user-id' => $userInfo['name']], true)),
                $view->form($form)
            );
        } else {
            $form = $this->formElementManager->get(ScriptoLoginForm::class);
            $form->setAttribute('action', $view->url(
                'scripto',
                ['action' => 'login'],
                ['query' => ['redirect' => $this->getCurrentUrl()]]
            ));
            return sprintf(
                '<div class="user logged-out"><a href="#" class="user-toggle page-link menu-toggle">%s</a><div class="user-menu">%s</div></div>',
                $view->translate('Log in'),
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
            switch ($bcRoute) {
                case 'admin/scripto-media':
                    $text = $view->scripto()->translate($view->project->itemType(), $this->bcRouteMap[$bcRoute]['text']);
                    break;
                case 'admin/scripto-media-id':
                    $text = $view->scripto()->translate($view->project->mediaType(), $this->bcRouteMap[$bcRoute]['text']);
                    break;
                default:
                    $text = $view->translate($this->bcRouteMap[$bcRoute]['text']);
            }
            $bc[] = $view->hyperlink($text, $view->url($bcRoute, $params));
        }
        switch ($routeName) {
            case 'admin/scripto-media':
                $text = $view->scripto()->translate($view->project->itemType(), $this->bcRouteMap[$routeName]['text']);
                break;
            case 'admin/scripto-media-id':
                $text = $view->scripto()->translate($view->project->mediaType(), $this->bcRouteMap[$routeName]['text']);
                break;
            default:
                $text = $view->translate($this->bcRouteMap[$routeName]['text']);
        }
        $bc[] = $view->translate($text);
        return sprintf('<div class="breadcrumbs">%s</div>', implode('<div class="separator"></div>', $bc));
    }

    /**
     * Render Scripto MediaWiki pagination.
     *
     * @return string
     */
    public function mediawikiPagination()
    {
        $view = $this->getView();
        return sprintf(
            '<nav class="pagination" role="navigation">%s%s</nav>',
            $view->hyperlink('', $view->url(null, [], true), [
                'class' => 'first o-icon-first button',
                'title' => $view->translate('First page'), // @translate
                'aria-label' => $view->translate('First page'), // @translate
            ]),
            $view->continue
                ? $view->hyperlink('', $view->url(null, [], ['query' => ['continue' => $view->continue]], true), [
                    'class' => 'next o-icon-next button',
                    'title' => $view->translate('Next page'), // @translate
                    'aria-label' => $view->translate('Next page'), // @translate
                ])
                : '<span class="next o-icon-next button inactive"></span>'
        );
    }

    /**
     * Render Scripto media pagination.
     *
     * @param string $action
     * @return string
     */
    public function mediaPagination($action = null)
    {
        $view = $this->getView();
        $sMedia = $view->sMedia;
        $previous = $sMedia->previousScriptoMedia();
        $next = $sMedia->nextScriptoMedia();
        return sprintf(
            '<nav class="pagination" role="navigation">%s%s%s</nav>',
            $previous
                ? $view->hyperlink('', $previous->url($action), ['class' => 'previous o-icon-prev button', 'title' => $view->translate('Previous')])
                : '<span class="previous o-icon-prev button inactive"></span>',
            $next
                ? $view->hyperlink('', $next->url($action), ['class' => 'next o-icon-next button', 'title' => $view->translate('Next')])
                : '<span class="next o-icon-next button inactive"></span>',
            sprintf(
                '<span class="row-count">%s</span>',
                sprintf($view->translate('%s of %s'), $sMedia->position(), $sMedia->scriptoItem()->mediaCount())
            )
        );
    }

    /**
     * Render Scripto compare revisions pagination.
     *
     * @param string $action
     * @return string
     */
    public function compareRevisionsPagination()
    {
        $view = $this->getView();
        $fromRevision = $view->fromRevision;
        $toRevision = $view->toRevision;
        return sprintf(
            '<nav class="pagination" role="navigation">%s%s</nav>',
            $fromRevision['parentid']
                ? $view->hyperlink('', $view->url(null, ['from-revision-id' => $fromRevision['parentid'], 'to-revision-id' => $fromRevision['revid']], true), ['class' => 'previous o-icon-prev button', 'title' => $view->translate('Older revision')])
                : '<span class="previous o-icon-prev button inactive"></span>',
            $toRevision['childid']
                ? $view->hyperlink('', $view->url(null, ['from-revision-id' => $toRevision['revid'], 'to-revision-id' => $toRevision['childid']], true), ['class' => 'next o-icon-next button', 'title' => $view->translate('Newer revision')])
                : '<span class="next o-icon-next button inactive"></span>'
        );
    }

    /**
     * Render the watchlist time period select.
     *
     * @return string
     */
    public function watchlistTimePeriodSelect()
    {
        $view = $this->getView();
        $timePeriods = [
            6 => $view->translate('6 hours'),
            12 => $view->translate('12 hours'),
            24 => $view->translate('1 day'),
            72 => $view->translate('3 days'),
            168 => $view->translate('7 days'),
            720 => $view->translate('30 days'),
            2160 => $view->translate('90 days'),
        ];
        $options = [];
        foreach ($timePeriods as $key => $value) {
            $options[] = sprintf(
                '<option value="%s"%s>%s</option>',
                $key, $key == $view->hours ? ' selected="selected"' : null, $value
            );
        }
        $html = <<<HTML
<div class="time-period filtering">
<label for="time-period">%s</label>
<select id="time-period" name="hours" data-url="%s">%s</select>
</div>
HTML;
        return sprintf(
            $html,
            $view->translate('Period of time to display:'), // @translate
            $view->escapeHtml($view->url(null, [], true)),
            implode(PHP_EOL, $options)
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
            $view->translate('Query'), // @translate
            $view->translate('Search') // @translate
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

    /**
     * Render the wtchlist toggle form.
     *
     * @param \Scripto\Api\Representation\ScriptoMediaRepresentation $sMedia
     * @return string
     */
    public function watchlistToggle($sMedia)
    {
        if (!$this->apiClient()->userIsLoggedIn()) {
            return;
        }

        $view = $this->getView();
        $userIsWatching = $sMedia->isWatched(0);

        $html = <<<'HTML'
<div id="watchlist-container" class="watch-list" data-url="%1$s" data-watching="%2$s">
    <a href="#" id="watchlist-watched" class="watchlist button watched" aria-label="%3$s" title="%3$s" style="%4$s">%3$s</a>
    <a href="#" id="watchlist-not-watched" class="watchlist button" aria-label="%5$s" title="%5$s" style="%6$s">%5$s</a>
    <div id="watch-success" class="watch success">%7$s</div>
    <div id="unwatch-success" class="unwatch success">%8$s</div>
</div>
HTML;
        return sprintf(
            $html,
            $view->escapeHtml($view->url(null, ['action' => 'watch'], true)),
            $view->escapeHtml($userIsWatching),
            $view->scripto()->translate($view->project->mediaType(), 'Stop tracking media'), // @translate
            $userIsWatching ? null : $view->escapeHtml('display: none;'),
            $view->scripto()->translate($view->project->mediaType(), 'Track media'), // @translate
            $userIsWatching ? $view->escapeHtml('display: none;') : null,
            $view->scripto()->translate($view->project->mediaType(), 'Now tracking media'), // @translate
            $view->scripto()->translate($view->project->mediaType(), 'No longer tracking media') // @translate
        );
    }

    /**
     * Get translations for the LML editor.
     *
     * @return string JSON encoded string of translations
     */
    public function getLmlEditorTranslations()
    {
        $view = $this->getView();
        return json_encode([
            'Italic' => $view->translate('Italic'),
            'Bold' => $view->translate('Bold'),
            'Strike out' => $view->translate('Strike out'),
            'Underline' => $view->translate('Underline'),
            'Blockquote' => $view->translate('Blockquote'),
            'Hidden comment' => $view->translate('Hidden comment'),
            'Level 1 heading' => $view->translate('Level 1 heading'),
            'Level 2 heading' => $view->translate('Level 2 heading'),
            'Level 3 heading' => $view->translate('Level 3 heading'),
            'Level 4 heading' => $view->translate('Level 4 heading'),
            'Level 5 heading' => $view->translate('Level 5 heading'),
            'Preformatted' => $view->translate('Preformatted'),
            'Horizontal rule' => $view->translate('Horizontal rule'),
            'Line break' => $view->translate('Line break'),
            'Signature' => $view->translate('Signature'),
            'Strikethrough' => $view->translate('Strikethrough'),
            'Unordered list' => $view->translate('Unordered list'),
            'Ordered list' => $view->translate('Ordered list'),
        ]);
    }

    /**
     * Get a project select object.
     *
     * @param string $name
     * @param string $value
     * @return Element\Select
     */
    public function getProjectSelect($name, $value = null)
    {
        $view = $this->getView();
        $options = [];
        $projects = $view->api()->search('scripto_projects')->getContent();
        foreach ($projects as $project) {
            $options[$project->id()] = $project->title();
        }
        $select = new Element\Select($name);
        $select->setEmptyOption($view->translate('Select one')); // @translate
        $select->setValueOptions($options);
        $select->setValue($value);
        return $select;
    }

    /**
     * Translate a string that must account for item, media, or content type.
     *
     * @param string $type item|media|content
     * @param string $string The string to translate
     * @return string The translated string
     */
    public function translate($type, $string)
    {
        $view = $this->getView();
        if (isset($this->typeStringMap[$string][$type])) {
            $string = $this->typeStringMap[$string][$type];
        }
        return $view->translate($string);
    }

    /**
     * Get the user signature for media talk page.
     *
     * MediaWiki's signature markup is not appropriate to use within Scripto
     * because it links back to the MediaWiki installation instead of Omeka.
     * This creates custom signature markup that links the user's name to their
     * public Scripto contributions page. Note that the links will break if the
     * domain changes in the meantime.
     *
     * @see https://www.mediawiki.org/wiki/Help:Signatures
     * @return string
     */
    public function getUserSignature()
    {
        $view = $this->getView();
        $userInfo = $this->apiClient->queryUserInfo();
        return sprintf(
            '-- [%s %s] ~~~~~',
            $view->url('scripto-user-contributions', ['user-id' => $userInfo['name']], ['force_canonical' => true]),
            $userInfo['name']
        );
    }
}
