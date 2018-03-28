<?php
namespace Scripto\Service\Form;

use Scripto\Form\BatchMediaForm;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class BatchMediaFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new BatchMediaForm(null, $options);
        $form->setApiClient($services->get('Scripto\Mediawiki\ApiClient'));
        return $form;
    }
}
