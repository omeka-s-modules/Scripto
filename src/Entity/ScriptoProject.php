<?php
namespace Scripto\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
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
     * @Column(type="boolean")
     */
    protected $isPublic = true;

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
     * @var array
     * @Column(
     *     type="json_array",
     *     nullable=true
     * )
     */
    protected $mediaTypes;

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
     * @Column(type="text", nullable=true)
     */
    protected $guidelines;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $createAccountText;

    /**
     * @Column(nullable=true)
     */
    protected $browseLayout;

    /**
     * @Column(type="boolean")
     */
    protected $filterApproved = false;

    /**
     * @Column(nullable=true)
     */
    protected $itemType;

    /**
     * @Column(nullable=true)
     */
    protected $mediaType;

    /**
     * @Column(nullable=true)
     */
    protected $contentType;

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

    /**
     * @OneToMany(
     *     targetEntity="ScriptoReviewer",
     *     mappedBy="scriptoProject",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove", "detach"},
     *     indexBy="user_id"
     * )
     */
    protected $reviewers;

    public function __construct()
    {
        $this->reviewers = new ArrayCollection;
    }

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

    public function setIsPublic($isPublic)
    {
        $this->isPublic = (bool) $isPublic;
    }

    public function getIsPublic()
    {
        return (bool) $this->isPublic;
    }

    public function setItemSet(ItemSet $itemSet = null)
    {
        $this->itemSet = $itemSet;
    }

    public function getItemSet()
    {
        return $this->itemSet;
    }

    public function setMediaTypes(array $mediaTypes = null)
    {
        $this->mediaTypes = $mediaTypes;
    }

    public function getMediaTypes()
    {
        return $this->mediaTypes;
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

    public function setGuidelines($guidelines)
    {
        $this->guidelines = $guidelines;
    }

    public function getGuidelines()
    {
        return $this->guidelines;
    }

    public function setCreateAccountText($createAccountText)
    {
        $this->createAccountText = $createAccountText;
    }

    public function getCreateAccountText()
    {
        return $this->createAccountText;
    }

    public function setBrowseLayout($browseLayout)
    {
        $this->browseLayout = $browseLayout;
    }

    public function getBrowseLayout()
    {
        return $this->browseLayout;
    }

    public function setFilterApproved($filterApproved)
    {
        $this->filterApproved = (bool) $filterApproved;
    }

    public function getFilterApproved()
    {
        return $this->filterApproved;
    }

    public function setItemType($itemType)
    {
        $this->itemType = $itemType;
    }

    public function getItemType()
    {
        return $this->itemType;
    }

    public function setMediaType($mediaType)
    {
        $this->mediaType = $mediaType;
    }

    public function getMediaType()
    {
        return $this->mediaType;
    }

    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    public function getContentType()
    {
        return $this->contentType;
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

    public function getReviewers()
    {
        return $this->reviewers;
    }

    /**
     * @PrePersist
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->setCreated(new DateTime('now'));
    }
}
