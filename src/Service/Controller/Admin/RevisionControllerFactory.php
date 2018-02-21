<?php
namespace Scripto\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Scripto\Controller\Admin\RevisionController;
use Zend\ServiceManager\Factory\FactoryInterface;

class RevisionControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new RevisionController($services->get('Scripto\Mediawiki\ApiClient'));
    }
}
