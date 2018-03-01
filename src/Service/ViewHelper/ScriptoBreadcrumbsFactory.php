<?php
namespace Scripto\Service\ViewHelper;

use Scripto\ViewHelper\ScriptoBreadcrumbs;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ScriptoBreadcrumbsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ScriptoBreadcrumbs($services->get('Application')->getMvcEvent()->getRouteMatch());
    }
}
