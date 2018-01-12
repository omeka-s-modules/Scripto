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
        $services->get('Omeka\Connection')->exec('
SET FOREIGN_KEY_CHECKS=0;
CREATE TABLE scripto_media (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, media_id INT NOT NULL, approved_by_id INT DEFAULT NULL, completed TINYINT(1) NOT NULL, completed_by VARCHAR(255) NOT NULL, approved TINYINT(1) NOT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL, INDEX IDX_28ABA038126F525E (item_id), INDEX IDX_28ABA038EA9FDD75 (media_id), INDEX IDX_28ABA0382D234F6A (approved_by_id), UNIQUE INDEX UNIQ_28ABA038126F525EEA9FDD75 (item_id, media_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
CREATE TABLE scripto_item (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, item_id INT NOT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL, INDEX IDX_2A827D37166D1F9C (project_id), INDEX IDX_2A827D37126F525E (item_id), UNIQUE INDEX UNIQ_2A827D37166D1F9C126F525E (project_id, item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
CREATE TABLE scripto_project (id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, item_set_id INT DEFAULT NULL, property_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL, INDEX IDX_E39E51087E3C61F9 (owner_id), INDEX IDX_E39E5108960278D7 (item_set_id), INDEX IDX_E39E5108549213EC (property_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
ALTER TABLE scripto_media ADD CONSTRAINT FK_28ABA038126F525E FOREIGN KEY (item_id) REFERENCES scripto_item (id) ON DELETE CASCADE;
ALTER TABLE scripto_media ADD CONSTRAINT FK_28ABA038EA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE CASCADE;
ALTER TABLE scripto_media ADD CONSTRAINT FK_28ABA0382D234F6A FOREIGN KEY (approved_by_id) REFERENCES user (id) ON DELETE SET NULL;
ALTER TABLE scripto_item ADD CONSTRAINT FK_2A827D37166D1F9C FOREIGN KEY (project_id) REFERENCES scripto_project (id) ON DELETE CASCADE;
ALTER TABLE scripto_item ADD CONSTRAINT FK_2A827D37126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;
ALTER TABLE scripto_project ADD CONSTRAINT FK_E39E51087E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL;
ALTER TABLE scripto_project ADD CONSTRAINT FK_E39E5108960278D7 FOREIGN KEY (item_set_id) REFERENCES item_set (id) ON DELETE SET NULL;
ALTER TABLE scripto_project ADD CONSTRAINT FK_E39E5108549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE SET NULL;
SET FOREIGN_KEY_CHECKS=1;
');
    }

    public function uninstall(ServiceLocatorInterface $services)
    {
        $services->get('Omeka\Connection')->exec('
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS scripto_media;
DROP TABLE IF EXISTS scripto_item;
DROP TABLE IF EXISTS scripto_project;
SET FOREIGN_KEY_CHECKS=1;
');
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $settings->delete('scripto_apiurl');
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
