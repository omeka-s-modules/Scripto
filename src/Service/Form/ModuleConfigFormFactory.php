<?php
namespace Scripto\Service\Form;

use Scripto\Form\ModuleConfigForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ModuleConfigFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ModuleConfigForm;
        $form->setApiClientDependencies(
            $services->get('Omeka\HttpClient'),
            $services->get('Omeka\Settings')->get('time_zone', 'UTC')
        );
        return $form;
    }
}
