<?php
namespace Scripto\Entity;

use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Media;
use Omeka\Entity\User;

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(
 *     uniqueConstraints={
 *         @UniqueConstraint(
 *             columns={"scripto_item_id", "media_id"}
 *         )
 *     }
 * )
 */
class ScriptoMedia extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(
     *     targetEntity="ScriptoItem"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $scriptoItem;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Media"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $media;

    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $isCompleted = false;

    /**
     * @Column(nullable=true)
     */
    protected $completedBy;

    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $isApproved = false;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\User"
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $approvedBy;

    /**
     * @Column(type="integer")
     */
    protected $position;

    /**
     * @Column(type="datetime")
     */
    protected $synced;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $edited;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $completed;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $approved;

    /**
     * Scripto media text
     *
     * Note that text is stored in MediaWiki, not Omeka. We use this property to
     * store text until persisting it using the Media API client.
     */
    protected $text;

    public function getId()
    {
        return $this->id;
    }

    public function setScriptoItem(ScriptoItem $scriptoItem)
    {
        $this->scriptoItem = $scriptoItem;
    }

    public function getScriptoItem()
    {
        return $this->scriptoItem;
    }

    public function setMedia(Media $media)
    {
        $this->media = $media;
    }

    public function getMedia()
    {
        return $this->media;
    }

    public function setIsCompleted($isCompleted)
    {
        $this->isCompleted = (bool) $isCompleted;
    }

    public function getIsCompleted()
    {
        return $this->isCompleted;
    }

    public function setCompletedBy($completedBy)
    {
        $this->completedBy = $completedBy;
    }

    public function getCompletedBy()
    {
        return $this->completedBy;
    }

    public function setIsApproved($isApproved)
    {
        $this->isApproved = (bool) $isApproved;
    }

    public function getIsApproved()
    {
        return $this->isApproved;
    }

    public function setApprovedBy(User $approvedBy = null)
    {
        $this->approvedBy = $approvedBy;
    }

    public function getApprovedBy()
    {
        return $this->approvedBy;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setSynced(DateTime $dateTime)
    {
        $this->synced = $dateTime;
    }

    public function getSynced()
    {
        return $this->synced;
    }

    public function setEdited(DateTime $dateTime)
    {
        $this->edited = $dateTime;
    }

    public function getEdited()
    {
        return $this->edited;
    }

    public function setCompleted(DateTime $dateTime)
    {
        $this->completed = $dateTime;
    }

    public function getCompleted()
    {
        return $this->completed;
    }

    public function setApproved(DateTime $dateTime)
    {
        $this->approved = $dateTime;
    }

    public function getApproved()
    {
        return $this->approved;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getMediawikiPageTitle()
    {
        return sprintf(
            '%s:%s:%s',
            $this->getScriptoItem()->getScriptoProject()->getId(),
            $this->getScriptoItem()->getItem()->getId(),
            $this->getMedia()->getId()
        );
    }

    /**
     * @PrePersist
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->setSynced(new DateTime('now'));
    }

    /**
     * @PreUpdate
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        if ($eventArgs->hasChangedField('isCompleted')) {
            $this->setCompleted(new DateTime('now'));
        }
        if ($eventArgs->hasChangedField('isApproved')) {
            $this->setApproved(new DateTime('now'));
        }
    }
}
