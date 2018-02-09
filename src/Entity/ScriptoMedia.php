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
     *     targetEntity="ScriptoItem",
     *     inversedBy="media"
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
     * @Column(type="integer")
     */
    protected $position;

    /**
     * @Column(nullable=true)
     */
    protected $completedBy;

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

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setCompletedBy($completedBy)
    {
        $this->completedBy = $completedBy;
    }

    public function getCompletedBy()
    {
        return $this->completedBy;
    }

    public function setApprovedBy(User $approvedBy = null)
    {
        $this->approvedBy = $approvedBy;
    }

    public function getApprovedBy()
    {
        return $this->approvedBy;
    }

    public function setSynced(DateTime $dateTime)
    {
        $this->synced = $dateTime;
    }

    public function getSynced()
    {
        return $this->synced;
    }

    public function setEdited(DateTime $dateTime = null)
    {
        $this->edited = $dateTime;
    }

    public function getEdited()
    {
        return $this->edited;
    }

    public function setCompleted(DateTime $dateTime = null)
    {
        $this->completed = $dateTime;
    }

    public function getCompleted()
    {
        return $this->completed;
    }

    public function setApproved(DateTime $dateTime = null)
    {
        $this->approved = $dateTime;
    }

    public function getApproved()
    {
        return $this->approved;
    }

    /**
     * Set Scripto media text.
     *
     * Note that text is stored in MediaWiki, not Omeka. We use this setter to
     * store text until persisting it using the Media API client.
     *
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * Get the Scripto media text.
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Get the title of the corresponding MediaWiki page.
     *
     * Every Scripto media maps to a MediaWiki page. For the page title we use
     * a unique sequence of IDs: the Scripto project ID, the Omeka item ID, and
     * the Omeka media ID, each separated by a colon.
     *
     * We use the IDs of Omeka entities (instead of the IDs of the corresponding
     * Scripto entities) so the pages are recoverable should an item be
     * accidentally removed from the project. It also allows for a possible
     * future feature of reconstitution of projects should the they be deleted.
     *
     * @return string
     */
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
}
