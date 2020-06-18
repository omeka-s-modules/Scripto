<?php
namespace Scripto\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Scripto\Controller\Admin\ProjectController;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ProjectControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ProjectController($services->get('Omeka\HtmlPurifier'));
    }
}
