<?php
namespace Scripto\Service\Mediawiki;

use Interop\Container\ContainerInterface;
use Scripto\Mediawiki\ApiClient;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ApiClientFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        $settings = $services->get('Omeka\Settings');
        return new ApiClient(
            $services->get('Omeka\HttpClient')->setOptions([
                // Decrease the chance of timeout by increasing to 20 seconds,
                // which is between Omeka's default (10) and MediaWiki's
                // default (25).
                'timeout' => 20,
                // Reuse one connection for the API requests made during a
                // single PHP request. Laminas defaults this to false, so each
                // one opened its own connection and, over HTTPS, its own TLS
                // handshake. A page view makes several: the public app layout
                // queries user info before it can draw the login bar.
                //
                // This matters most where something in the request path
                // inspects or throttles connections, such as a proxy or web
                // application firewall in front of the site.
                //
                // No effect if the site configures the cURL adapter, which
                // opens a fresh handle per request and cannot reuse a
                // connection. Omeka's default Socket adapter can.
                'keepalive' => true,
            ]),
            $settings->get('scripto_apiurl'),
            $settings->get('time_zone', 'UTC')
        );
    }
}
