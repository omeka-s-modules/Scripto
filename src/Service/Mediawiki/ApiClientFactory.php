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
            'http://localhost/mediawiki-1.29.1/api.php',
            $services->get('ViewHelperManager')->get('ServerUrl')->__invoke()
        );
    }
}
