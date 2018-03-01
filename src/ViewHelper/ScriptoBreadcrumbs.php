<?php
namespace Scripto\ViewHelper;

use Zend\View\Helper\AbstractHelper;

class ScriptoBreadcrumbs extends AbstractHelper
{
    protected $routeMatch;

    public function __construct($routeMatch)
    {
        $this->routeMatch = $routeMatch;
    }

    public function __invoke()
    {
        $bc = [];
        $view = $this->getView();
        $routeName = $this->routeMatch->getMatchedRouteName();

        $bc[] = $view->hyperlink($view->translate('Project browse'), $view->url('admin/scripto-project'));

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
}
