<?php
namespace Scripto\Service\Form;

use Scripto\Form\CreateAccountForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class CreateAccountFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $apiClient = $services->get('Scripto\Mediawiki\ApiClient');

        $form = new CreateAccountForm(null, ['fields' => $apiClient->getCreateAccountFields()]);

        return $form;
    }
}
