<?php
namespace Scripto\Entity;

use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
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
    protected $completed = false;

    /**
     * @Column(nullable=true)
     */
    protected $completedBy;

    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $approved = false;

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
     * @Column(type="datetime")
     */
    protected $created;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $modified;

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

    public function setCompleted($completed)
    {
        $this->completed = (bool) $completed;
    }

    public function getCompleted()
    {
        return $this->completed;
    }

    public function setCompletedBy($completedBy)
    {
        $this->completedBy = $completedBy;
    }

    public function getCompletedBy()
    {
        return $this->completedBy;
    }

    public function setApproved($approved)
    {
        $this->approved = (bool) $completed;
    }

    public function getApproved()
    {
        return $this->approved;
    }

    public function setApprovedBy(User $approvedBy = null)
    {
        $this->approvedBy = $approvedBy;
    }

    public function getApprovedBy()
    {
        return $this->approvedBy;
    }

    public function setCreated(DateTime $dateTime)
    {
        $this->created = $dateTime;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setModified(DateTime $dateTime)
    {
        $this->modified = $dateTime;
    }

    public function getModified()
    {
        return $this->modified;
    }

    /**
     * @PrePersist
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->created = new DateTime('now');
    }
}
