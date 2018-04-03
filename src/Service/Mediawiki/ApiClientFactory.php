<?php
namespace Scripto\Service\Mediawiki;

use Interop\Container\ContainerInterface;
use Scripto\Mediawiki\ApiClient;
use Zend\ServiceManager\Factory\FactoryInterface;

class ApiClientFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $settings = $services->get('Omeka\Settings');
        return new ApiClient(
            $services->get('Omeka\HttpClient'),
            $settings->get('scripto_apiurl'),
            $settings->get('time_zone', 'UTC')
        );
    }
}
