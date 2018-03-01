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
        return $this->getView()->partial('scripto/breadcrumbs', ['routeMatch' => $this->routeMatch]);
    }
}
