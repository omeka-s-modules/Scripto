<?php
namespace Scripto\Entity;

use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\ItemSet;
use Omeka\Entity\Property;
use Omeka\Entity\User;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class ScriptoProject extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\User"
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $owner;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\ItemSet"
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $itemSet;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Property"
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $property;

    /**
     * @Column(nullable=true)
     */
    protected $lang;

    /**
     * @Column(nullable=true)
     */
    protected $importTarget;

    /**
     * @Column
     */
    protected $title;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @Column(type="datetime")
     */
    protected $created;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $synced;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $imported;

    public function getId()
    {
        return $this->id;
    }

    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setItemSet(ItemSet $itemSet = null)
    {
        $this->itemSet = $itemSet;
    }

    public function getItemSet()
    {
        return $this->itemSet;
    }

    public function setProperty(Property $property = null)
    {
        $this->property = $property;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    public function getLang()
    {
        return $this->lang;
    }

    public function setImportTarget($importTarget)
    {
        $this->importTarget = $importTarget;
    }

    public function getImportTarget()
    {
        return $this->importTarget;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setCreated(DateTime $dateTime)
    {
        $this->created = $dateTime;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setSynced(DateTime $dateTime = null)
    {
        $this->synced = $dateTime;
    }

    public function getSynced()
    {
        return $this->synced;
    }

    public function setImported(DateTime $dateTime = null)
    {
        $this->imported = $dateTime;
    }

    public function getImported()
    {
        return $this->imported;
    }

    /**
     * @PrePersist
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->setCreated(new DateTime('now'));
    }
}
