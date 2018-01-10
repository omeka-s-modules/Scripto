<?php
namespace Scripto\Service\Form;

use Scripto\Form\ConfigForm;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ConfigFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ConfigForm;
        $form->setHttpClient($services->get('Omeka\HttpClient'));
        return $form;
    }
}
