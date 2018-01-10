<?php
namespace Scripto;

use Omeka\Module\AbstractModule;
use Scripto\Form\ConfigForm;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\PhpRenderer;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
    }

    public function install(ServiceLocatorInterface $services)
    {
    }

    public function uninstall(ServiceLocatorInterface $services)
    {
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Scripto\Form\ConfigForm');
        $form->init();
        $form->setData([
            'apiurl' => $settings->get('scripto_apiurl'),
        ]);
        return $renderer->formCollection($form, false);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Scripto\Form\ConfigForm');
        $form->init();
        $form->setData($controller->params()->fromPost());
        if ($form->isValid()) {
            $formData = $form->getData();
            $settings->set('scripto_apiurl', $formData['apiurl']);
            return true;
        }
        $controller->messenger()->addErrors($form->getMessages());
        return false;
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
    }
}
