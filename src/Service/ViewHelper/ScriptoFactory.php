<?php
namespace Scripto\Service\ViewHelper;

use Scripto\ViewHelper\Scripto;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ScriptoFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Scripto(
            $services->get('Scripto\Mediawiki\ApiClient'),
            $services->get('FormElementManager'),
            $services->get('Application')->getMvcEvent()->getRouteMatch()
        );
    }
}
