<?php
namespace Scripto\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Scripto\ControllerPlugin\Scripto;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ScriptoFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Scripto($services->get('Scripto\Mediawiki\ApiClient'));
    }
}
