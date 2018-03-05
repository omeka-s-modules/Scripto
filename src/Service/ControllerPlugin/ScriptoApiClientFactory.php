<?php
namespace Scripto\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Scripto\ControllerPlugin\ScriptoApiClient;
use Zend\ServiceManager\Factory\FactoryInterface;

class ScriptoApiClientFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ScriptoApiClient($services->get('Scripto\Mediawiki\ApiClient'));
    }
}
