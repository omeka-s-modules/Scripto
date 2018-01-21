<?php
namespace Scripto\Service\Mediawiki;

use Interop\Container\ContainerInterface;
use Scripto\Mediawiki\ApiClient;
use Zend\ServiceManager\Factory\FactoryInterface;

class ApiClientFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ApiClient(
            $services->get('Omeka\HttpClient'),
            $services->get('Omeka\Settings')->get('scripto_apiurl'),
            $services->get('ViewHelperManager')->get('ServerUrl')->__invoke()
        );
    }
}
