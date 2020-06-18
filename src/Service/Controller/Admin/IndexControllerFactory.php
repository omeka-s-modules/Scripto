<?php
namespace Scripto\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Scripto\Controller\Admin\IndexController;
use Laminas\ServiceManager\Factory\FactoryInterface;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new IndexController($services->get('Omeka\AuthenticationService'));
    }
}
