<?php
namespace Scripto;

use Composer\Semver\Comparator;
use DateTime;
use Omeka\Module\AbstractModule;
use Omeka\Mvc\Exception\RuntimeException as MvcRuntimeException;
use Scripto\PermissionsAssertion\ProjectIsPublicAssertion;
use Scripto\PermissionsAssertion\UserCanReviewAssertion;
use Scripto\PermissionsAssertion\UserOwnsProjectAssertion;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\MvcEvent;
use Laminas\Permissions\Acl\Assertion\AssertionAggregate;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;

class Module extends AbstractModule
{
    /**
     * @var Cache
     */
    protected $cache = [];

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
        $this->addAclRules();

        // Set the corresponding visibility rules on Scripto resources.
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $filter = $em->getFilters()->getFilter('resource_visibility');
        $filter->addRelatedEntity('Scripto\Entity\ScriptoItem', 'item_id');
        $filter->addRelatedEntity('Scripto\Entity\ScriptoMedia', 'media_id');
    }

    public function install(ServiceLocatorInterface $services)
    {
        $conn = $services->get('Omeka\Connection');
        $conn->exec('SET FOREIGN_KEY_CHECKS=0;');
        $conn->exec('CREATE TABLE scripto_reviewer (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, scripto_project_id INT NOT NULL, INDEX IDX_A9E24DFCA76ED395 (user_id), INDEX IDX_A9E24DFCDC45463D (scripto_project_id), UNIQUE INDEX UNIQ_A9E24DFCA76ED395DC45463D (user_id, scripto_project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->exec('CREATE TABLE scripto_media (id INT AUTO_INCREMENT NOT NULL, scripto_item_id INT NOT NULL, media_id INT NOT NULL, approved_by_id INT DEFAULT NULL, position INT NOT NULL, synced DATETIME NOT NULL, edited DATETIME DEFAULT NULL, edited_by VARCHAR(255) DEFAULT NULL, completed DATETIME DEFAULT NULL, completed_by VARCHAR(255) DEFAULT NULL, completed_revision INT DEFAULT NULL, approved DATETIME DEFAULT NULL, approved_revision INT DEFAULT NULL, imported_html LONGTEXT DEFAULT NULL, INDEX IDX_28ABA038DE42D3B8 (scripto_item_id), INDEX IDX_28ABA038EA9FDD75 (media_id), INDEX IDX_28ABA0382D234F6A (approved_by_id), UNIQUE INDEX UNIQ_28ABA038DE42D3B8EA9FDD75 (scripto_item_id, media_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->exec('CREATE TABLE scripto_item (id INT AUTO_INCREMENT NOT NULL, scripto_project_id INT NOT NULL, item_id INT NOT NULL, synced DATETIME NOT NULL, edited DATETIME DEFAULT NULL, INDEX IDX_2A827D37DC45463D (scripto_project_id), INDEX IDX_2A827D37126F525E (item_id), UNIQUE INDEX UNIQ_2A827D37DC45463D126F525E (scripto_project_id, item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->exec('CREATE TABLE scripto_project (id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, item_set_id INT DEFAULT NULL, property_id INT DEFAULT NULL, is_public TINYINT(1) NOT NULL, media_types LONGTEXT DEFAULT NULL COMMENT "(DC2Type:json_array)", lang VARCHAR(255) DEFAULT NULL, import_target VARCHAR(255) DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, guidelines LONGTEXT DEFAULT NULL, create_account_text LONGTEXT DEFAULT NULL, browse_layout VARCHAR(255) DEFAULT NULL, filter_approved TINYINT(1) NOT NULL, item_type VARCHAR(255) DEFAULT NULL, media_type VARCHAR(255) DEFAULT NULL, content_type VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, synced DATETIME DEFAULT NULL, imported DATETIME DEFAULT NULL, INDEX IDX_E39E51087E3C61F9 (owner_id), INDEX IDX_E39E5108960278D7 (item_set_id), INDEX IDX_E39E5108549213EC (property_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->exec('ALTER TABLE scripto_reviewer ADD CONSTRAINT FK_A9E24DFCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE scripto_reviewer ADD CONSTRAINT FK_A9E24DFCDC45463D FOREIGN KEY (scripto_project_id) REFERENCES scripto_project (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE scripto_media ADD CONSTRAINT FK_28ABA038DE42D3B8 FOREIGN KEY (scripto_item_id) REFERENCES scripto_item (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE scripto_media ADD CONSTRAINT FK_28ABA038EA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE scripto_media ADD CONSTRAINT FK_28ABA0382D234F6A FOREIGN KEY (approved_by_id) REFERENCES user (id) ON DELETE SET NULL;');
        $conn->exec('ALTER TABLE scripto_item ADD CONSTRAINT FK_2A827D37DC45463D FOREIGN KEY (scripto_project_id) REFERENCES scripto_project (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE scripto_item ADD CONSTRAINT FK_2A827D37126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE scripto_project ADD CONSTRAINT FK_E39E51087E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL;');
        $conn->exec('ALTER TABLE scripto_project ADD CONSTRAINT FK_E39E5108960278D7 FOREIGN KEY (item_set_id) REFERENCES item_set (id) ON DELETE SET NULL;');
        $conn->exec('ALTER TABLE scripto_project ADD CONSTRAINT FK_E39E5108549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE SET NULL;');
        $conn->exec('SET FOREIGN_KEY_CHECKS=1;');

        // Import the Scripto vocabulary if it doesn't already exist.
        $api = $services->get('Omeka\ApiManager');
        $response = $api->search('vocabularies', [
            'namespace_uri' => 'http://scripto.org/vocab#',
            'limit' => 0,
        ]);
        if (0 === $response->getTotalResults()) {
            $importer = $services->get('Omeka\RdfImporter');
            $importer->import(
                'file',
                [
                    'o:namespace_uri' => 'http://scripto.org/vocab#',
                    'o:prefix' => 'scripto',
                    'o:label' => 'Scripto',
                    'o:comment' => null,
                ],
                [
                    'file' => __DIR__ . '/vocabs/scripto.n3',
                    'format' => 'turtle',
                ]
            );
        }
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $services)
    {
        $conn = $services->get('Omeka\Connection');
        if (Comparator::lessThan($oldVersion, '0.1.0-alpha2')) {
            $conn->exec('ALTER TABLE scripto_project ADD browse_layout VARCHAR(255) DEFAULT NULL');
        }
        if (Comparator::lessThan($oldVersion, '0.1.0-alpha3')) {
            $conn->exec('ALTER TABLE scripto_project ADD item_type VARCHAR(255) DEFAULT NULL, ADD media_type VARCHAR(255) DEFAULT NULL, ADD content_type VARCHAR(255) DEFAULT NULL');
        }
        if (Comparator::lessThan($oldVersion, '1.0.0-beta2')) {
            $conn->exec('ALTER TABLE scripto_project ADD filter_approved TINYINT(1) NOT NULL');
        }
        if (Comparator::lessThan($oldVersion, '1.0.0-beta3')) {
            $conn->exec('ALTER TABLE scripto_project ADD create_account_text LONGTEXT DEFAULT NULL');
        }
        if (Comparator::lessThan($oldVersion, '1.0.1')) {
            $conn->exec('ALTER TABLE scripto_project ADD media_types LONGTEXT DEFAULT NULL COMMENT "(DC2Type:json_array)" AFTER is_public');
        }
    }

    public function uninstall(ServiceLocatorInterface $services)
    {
        $conn = $services->get('Omeka\Connection');
        $conn->exec('SET FOREIGN_KEY_CHECKS=0;');
        $conn->exec('DROP TABLE IF EXISTS scripto_reviewer;');
        $conn->exec('DROP TABLE IF EXISTS scripto_media;');
        $conn->exec('DROP TABLE IF EXISTS scripto_item;');
        $conn->exec('DROP TABLE IF EXISTS scripto_project;');
        $conn->exec('SET FOREIGN_KEY_CHECKS=1;');

        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $settings->delete('scripto_apiurl');

        // Note that we do not delete the Scripto vocabulary.
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Scripto\Form\ModuleConfigForm');
        $form->init();
        $form->setData([
            'apiurl' => $settings->get('scripto_apiurl'),
        ]);
        return $renderer->formCollection($form, false);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Scripto\Form\ModuleConfigForm');
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
        // Add the Scripto term definition.
        $sharedEventManager->attach(
            '*',
            'api.context',
            function (Event $event) {
                $context = $event->getParam('context');
                $context['o-module-scripto'] = 'http://omeka.org/s/vocabs/module/scripto#';
                $event->setParam('context', $context);
            }
        );
        // Add the imported Scripto items tab to the section navigation.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.section_nav',
            function (Event $event) {
                $itemId = $event->getParam('resource')->id();
                $sItems = $this->getImportedScriptoItems($itemId);
                if ($sItems) {
                    $view = $event->getTarget();
                    $sectionNav = $event->getParam('section_nav');
                    foreach ($sItems as $sItem) {
                        $project = $sItem[0]->scriptoProject();
                        $sectionId = sprintf('imported-scripto-item-%s', $sItem[0]->id());
                        $tabText = sprintf(
                            '%s%s',
                            ucfirst($project->property()->label()),
                            $project->lang() ? sprintf(' (%s)', $project->lang()) : ''
                        );
                        $sectionNav[$sectionId] = $view->translate($tabText);
                    }
                    $event->setParam('section_nav', $sectionNav);
                }
            }
        );
        // Add the imported Scripto media tab to the section navigation.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.show.section_nav',
            function (Event $event) {
                $mediaId = $event->getParam('resource')->id();
                $sMedias = $this->getImportedScriptoMedia($mediaId);
                if ($sMedias) {
                    $view = $event->getTarget();
                    $sectionNav = $event->getParam('section_nav');
                    foreach ($sMedias as $sMedia) {
                        $project = $sMedia->scriptoItem()->scriptoProject();
                        $sectionId = sprintf('imported-scripto-media-%s', $sMedia->id());
                        $tabText = sprintf(
                            '%s%s',
                            ucfirst($project->property()->label()),
                            $project->lang() ? sprintf(' (%s)', $project->lang()) : ''
                        );
                        $sectionNav[$sectionId] = $view->translate($tabText);
                    }
                    $event->setParam('section_nav', $sectionNav);
                }
            }
        );
        // Add the imported Scripto items panel to the item show page.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.after',
            function (Event $event) {
                $item = $event->getTarget()->item;
                $sItems = $this->getImportedScriptoItems($item->id());
                if ($sItems) {
                    foreach ($sItems as $sItem) {
                        $sectionId = sprintf('imported-scripto-item-%s', $sItem[0]->id());
                        echo $event->getTarget()->partial(
                            'scripto/admin/imported-scripto-item',
                            ['item' => $item, 'sItem' => $sItem, 'sectionId' => $sectionId]
                        );
                    }
                }
            }
        );
        // Add the imported Scripto media panel to the media show page.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.show.after',
            function (Event $event) {
                $media = $event->getTarget()->media;
                $sMedias = $this->getImportedScriptoMedia($media->id());
                if ($sMedias) {
                    foreach ($sMedias as $sMedia) {
                        $sectionId = sprintf('imported-scripto-media-%s', $sMedia->id());
                        echo $event->getTarget()->partial(
                            'scripto/admin/imported-scripto-media',
                            ['media' => $media, 'sMedia' => $sMedia, 'sectionId' => $sectionId]
                        );
                    }
                }
            }
        );
        $sharedEventManager->attach(
            '*',
            'dispatch',
            [$this, 'setPublicAppLayout']
        );
        $sharedEventManager->attach(
            '*',
            'route',
            [$this, 'checkMediawikiApiUrl']
        );
        $sharedEventManager->attach(
            'Scripto\Api\Adapter\ScriptoMediaAdapter',
            'api.hydrate.post',
            [$this, 'editMediawikiPage']
        );
        $sharedEventManager->attach(
            'Scripto\Api\Adapter\ScriptoProjectAdapter',
            'api.search.query',
            [$this, 'filterProjects']
        );
        $sharedEventManager->attach(
            'Scripto\Api\Adapter\ScriptoProjectAdapter',
            'api.find.query',
            [$this, 'filterProjects']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Representation\ItemRepresentation',
            'rep.resource.json',
            [$this, 'filterItemJsonLd']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Representation\MediaRepresentation',
            'rep.resource.json',
            [$this, 'filterMediaJsonLd']
        );
    }

    /**
     * Add ACL rules for this module.
     */
    protected function addAclRules()
    {
        $acl = $this->getServiceLocator()->get('Omeka\Acl');

        // Set controller/action privileges.
        $acl->allow(
            null,
            [
                'Scripto\Controller\PublicApp\Index',
                'Scripto\Controller\PublicApp\Item',
                'Scripto\Controller\PublicApp\Media',
                'Scripto\Controller\PublicApp\Project',
                'Scripto\Controller\PublicApp\Revision',
                'Scripto\Controller\PublicApp\User',
            ]
        );
        $acl->allow(
            null,
            'Scripto\Controller\Admin\Index',
            ['index', 'login', 'logout']
        );
        $acl->allow(
            null,
            'Scripto\Controller\Admin\User',
            ['contributions', 'watchlist']
        );
        $acl->allow(
            null,
            'Scripto\Controller\Admin\Project',
            ['browse', 'show-details', 'show']
        );
        $acl->allow(
            null,
            'Scripto\Controller\Admin\Item',
            ['browse', 'show-details', 'show']
        );
        $acl->allow(
            null,
            'Scripto\Controller\Admin\Media',
            ['browse', 'show-details', 'show', 'batch-edit', 'batch-edit-all']
        );
        $acl->allow(
            null,
            'Scripto\Controller\Admin\Revision',
            ['browse', 'compare']
        );

        // Set API adapter privileges.
        $acl->allow(
            null,
            [
                'Scripto\Api\Adapter\ScriptoProjectAdapter',
                'Scripto\Api\Adapter\ScriptoItemAdapter',
                'Scripto\Api\Adapter\ScriptoMediaAdapter',
            ],
            ['search', 'read', 'view_scripto_media_batch_update']
        );
        $acl->allow(
            null,
            [
                'Scripto\Api\Adapter\ScriptoMediaAdapter',
            ],
            ['update', 'batch_update']
        );

        // Set entity privileges.
        $viewerAssertion = new AssertionAggregate;
        $viewerAssertion->addAssertions([
            new ProjectIsPublicAssertion,
            new UserOwnsProjectAssertion,
            new UserCanReviewAssertion,
        ]);
        $viewerAssertion->setMode(AssertionAggregate::MODE_AT_LEAST_ONE);
        $acl->allow(
            null,
            [
                'Scripto\Entity\ScriptoProject',
                'Scripto\Entity\ScriptoItem',
                'Scripto\Entity\ScriptoMedia',
            ],
            'read',
            $viewerAssertion
        );
        $acl->allow(
            null,
            'Scripto\Entity\ScriptoMedia',
            'update'
        );
        $acl->allow(
            null,
            'Scripto\Entity\ScriptoMedia',
            ['batch_update', 'review'],
            new UserCanReviewAssertion
        );
        $acl->allow(
            null,
            'Scripto\Entity\ScriptoItem',
            'view_scripto_media_batch_update',
            new UserCanReviewAssertion
        );
    }

    /**
     * Set the public application layout.
     *
     * @param Event $event
     */
    public function setPublicAppLayout(Event $event)
    {
        $routeName = $event->getRouteMatch()->getMatchedRouteName();
        if (0 !== strpos($routeName, 'scripto')) {
            // Not a public application Scripto route.
            return;
        }
        $event->getViewModel()->setTemplate('layout/layout-scripto-public-app');
    }

    /**
     * Check for MediaWiki API URL.
     *
     * Blocks access to all Scripto routes if the MediaWiki API URL is not set.
     *
     * @param Event $event
     */
    public function checkMediawikiApiUrl(Event $event)
    {
        $services = $this->getServiceLocator();
        $routeName = $event->getRouteMatch()->getMatchedRouteName();
        if (0 !== strpos($routeName, 'admin/scripto') && 0 !== strpos($routeName, 'scripto')) {
            // Not an admin or public Scripto route.
            return;
        }
        $settings = $services->get('Omeka\Settings');
        if ($settings->get('scripto_apiurl')) {
            // The MediaWiki API URL is not set.
            return;
        }
        $translator = $services->get('MvcTranslator');
        throw new MvcRuntimeException($translator->translate('Cannot access Scripto. Missing MediaWiki API URL.'));
    }

    /**
     * Create or edit a MediaWiki page given a Scripto media entity.
     *
     * Attaches to the api.hydrate.post event to ensure that the corresponding
     * MediaWiki page is successfully created/edited prior to updating the
     * Scripto media entity. Ideally we'd use entity.update.pre to ensure that
     * the entity is validated, but it isn't triggered when there are no changes
     * to the entity (i.e. when only the text has changed).
     *
     * @param Event $event
     */
    public function editMediawikiPage(Event $event)
    {
        $sMedia = $event->getParam('entity');

        if (!is_string($sMedia->getWikitextData('wikitext'))) {
            // No need to edit the MediaWiki page if text is null.
            return;
        }

        $services = $this->getServiceLocator();
        $client = $services->get('Scripto\Mediawiki\ApiClient');

        $pageTitle = $sMedia->getMediawikiPageTitle();
        $page = $client->queryPage($pageTitle);
        $pageIsCreated = $client->pageIsCreated($page);

        if (!$pageIsCreated && !$client->userCan($page, 'createpage')) {
            throw new \Exception(sprintf(
                $services->get('MvcTranslator')->translate('The MediaWiki user does not have the necessary permissions to create the page "%s"'),
                $pageTitle
            ));
        }
        if ($pageIsCreated && !$client->userCan($page, 'edit')) {
            throw new \Exception(sprintf(
                $services->get('MvcTranslator')->translate('The MediaWiki user does not have the necessary permissions to edit the page "%s"'),
                $pageTitle
            ));
        }

        $result = $client->editPage(
            $pageTitle,
            $sMedia->getWikitextData('wikitext'),
            $sMedia->getWikitextData('summary')
        );

        if (!isset($result['nochange'])) {
            // Update edited user and datetime only if there was a change.
            $user = $client->queryUserInfo();
            $sMedia->setEditedBy($user['name']);

            $now = new DateTime('now');
            $sMedia->setEdited($now);
            $sMedia->getScriptoItem()->setEdited($now);
        }

        // Conditionally set the newest revision as completed/approved.
        if ($sMedia->getWikitextData('mark_complete')
            && !$sMedia->getCompletedRevision()
        ) {
            $revisionId = isset($result['nochange']) ? $page['lastrevid'] : $result['newrevid'];
            $sMedia->setCompletedRevision($revisionId);
        }
        if ($sMedia->getWikitextData('mark_approved')
            && !$sMedia->getApprovedRevision()
            && $services->get('Omeka\Acl')->userIsAllowed($sMedia, 'review')
        ) {
            $revisionId = isset($result['nochange']) ? $page['lastrevid'] : $result['newrevid'];
            $sMedia->setApprovedRevision($revisionId);
        }
    }

    /**
     * Filter private projects.
     *
     * @param Event $event
     */
    public function filterProjects(Event $event)
    {
        $qb = $event->getParam('queryBuilder');

        // Users can view projects they do not own that are public.
        $expression = $qb->expr()->eq("omeka_root.isPublic", true);

        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $acl = $this->getServiceLocator()->get('Omeka\Acl');

        if ($auth->hasIdentity()) {
            $identity = $auth->getIdentity();
            if ($acl->isAdminRole($identity->getRole())) {
                // Admin users can view all projects.
                return;
            }
            $adapter = $event->getTarget();
            $projectAlias = $adapter->createAlias();
            $qb->leftJoin('omeka_root.reviewers', $projectAlias);
            $expression = $qb->expr()->orX(
                $expression,
                // Users can view projects they own.
                $qb->expr()->eq(
                    "omeka_root.owner",
                    $adapter->createNamedParameter($qb, $identity)
                ),
                // Users can view projects that they review.
                $qb->expr()->eq(
                    "$projectAlias.user",
                    $adapter->createNamedParameter($qb, $identity)
                )
            );
        }
        $qb->andWhere($expression);
    }

    /**
     * Add imported Scripto items to the corresponding Omeka item's JSON-LD.
     *
     * Event $event
     */
    public function filterItemJsonLd(Event $event)
    {
        $jsonLd = $event->getParam('jsonLd');
        $sItems = $this->getImportedScriptoItems($event->getTarget()->id());
        foreach ($sItems as $sItem) {
            $project = $sItem[0]->scriptoProject();
            $jsonLd['o-module-scripto:content'][] = [
                'o:property' => $project->property()->getReference(),
                'o:lang' => $project->lang(),
                'o-module-scripto:html' => $sItem[1],
            ];
        }
        $event->setParam('jsonLd', $jsonLd);
    }

    /**
     * Add imported Scripto media to the corresponding Omeka media's JSON-LD.
     *
     * Event $event
     */
    public function filterMediaJsonLd(Event $event)
    {
        $jsonLd = $event->getParam('jsonLd');
        $sMedias = $this->getImportedScriptoMedia($event->getTarget()->id());
        foreach ($sMedias as $sMedia) {
            $project = $sMedia->scriptoItem()->scriptoProject();
            $jsonLd['o-module-scripto:content'][] = [
                'o:property' => $project->property()->getReference(),
                'o:lang' => $project->lang(),
                'o-module-scripto:html' => $sMedia->importedHtml(),
            ];
        }
        $event->setParam('jsonLd', $jsonLd);
    }

    /**
     * Get imported Scripto media.
     *
     * Caches the data to avoid duplicate queries during the request. Returns an
     * array containing Scripto media representations.
     *
     * @param int $mediaId
     * @return array
     */
    public function getImportedScriptoMedia($mediaId)
    {
        if (!isset($this->cache['imported_scripto_resources'][$mediaId])) {
            $em = $this->getServiceLocator()->get('Omeka\EntityManager');
            $dql = '
            SELECT sm
            FROM Scripto\Entity\ScriptoMedia sm
            JOIN sm.media m WITH m.id = :media_id
            WHERE sm.importedHtml IS NOT NULL';
            $sMediaEntities = $em->createQuery($dql)->setParameter('media_id', $mediaId)->getResult();
            $sMedia = [];
            if ($sMediaEntities) {
                $sMediaAdapter = $this->getServiceLocator()->get('Omeka\ApiAdapterManager')->get('scripto_media');
                foreach ($sMediaEntities as $sMediaEntity) {
                    $sMedia[] = $sMediaAdapter->getRepresentation($sMediaEntity);
                }
            }
            $this->cache['imported_scripto_resources'][$mediaId] = $sMedia;
        }
        return $this->cache['imported_scripto_resources'][$mediaId];
    }

    /**
     * Get imported Scripto items.
     *
     * Caches the data to avoid duplicate queries during the request. Returns an
     * array of arrays containing [0] Scripto item representations and [1]
     * aggregated imported HTML.
     *
     * @param int $itemId
     * @return array
     */
    public function getImportedScriptoItems($itemId)
    {
        if (!isset($this->cache['imported_scripto_resources'][$itemId])) {
            $em = $this->getServiceLocator()->get('Omeka\EntityManager');
            $dql = '
            SELECT si, GROUP_CONCAT(sm.importedHtml ORDER BY sm.position ASC SEPARATOR \'\')
            FROM Scripto\Entity\ScriptoItem si
            JOIN si.item i WITH i.id = :item_id
            LEFT JOIN si.scriptoMedia sm
            WHERE sm.importedHtml IS NOT NULL
            GROUP BY si';
            $sItemEntities = $em->createQuery($dql)->setParameter('item_id', $itemId)->getResult();
            $sItems = [];
            if ($sItemEntities) {
                $sItemAdapter = $this->getServiceLocator()->get('Omeka\ApiAdapterManager')->get('scripto_items');
                foreach ($sItemEntities as $sItemEntity) {
                    $sItems[] = [
                        $sItemAdapter->getRepresentation($sItemEntity[0]),
                        $sItemEntity[1],
                    ];
                }
            }
            $this->cache['imported_scripto_resources'][$itemId] = $sItems;
        }
        return $this->cache['imported_scripto_resources'][$itemId];
    }
}
