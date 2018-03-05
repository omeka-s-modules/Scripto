<?php
namespace Scripto\Service\ViewHelper;

use Scripto\ViewHelper\ScriptoAuth;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ScriptoAuthFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ScriptoAuth(
            $services->get('Scripto\Mediawiki\ApiClient'),
            $services->get('FormElementManager')
        );
    }
}
