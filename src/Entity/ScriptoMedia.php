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
     * @Column(type="datetime")
     */
    protected $synced;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $edited;

    /**
     * @Column(nullable=true)
     */
    protected $editedBy;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $completed;

    /**
     * @Column(nullable=true)
     */
    protected $completedBy;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $completedRevision;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $approved;

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
     * @Column(type="integer", nullable=true)
     */
    protected $approvedRevision;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $importedHtml;

    protected $wikitextData = [
        'wikitext' => null,
        'summary' => null,
        'mark_complete' => false,
        'mark_approved' => false,
    ];

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

    public function setEditedBy($editedBy)
    {
        $this->editedBy = $editedBy;
    }

    public function getEditedBy()
    {
        return $this->editedBy;
    }

    public function setCompleted(DateTime $dateTime = null)
    {
        $this->completed = $dateTime;
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

    public function setCompletedRevision($completedRevision)
    {
        $this->completedRevision = $completedRevision;
    }

    public function getCompletedRevision()
    {
        return $this->completedRevision;
    }

    public function setApproved(DateTime $dateTime = null)
    {
        $this->approved = $dateTime;
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

    public function setApprovedRevision($approvedRevision)
    {
        $this->approvedRevision = $approvedRevision;
    }

    public function getApprovedRevision()
    {
        return $this->approvedRevision;
    }

    public function setImportedHtml($importedHtml)
    {
        $this->importedHtml = $importedHtml;
    }

    public function getImportedHtml()
    {
        return $this->importedHtml;
    }

    /**
     * Set Scripto media wikitext data.
     *
     * Note that wikitext is stored in MediaWiki, not Omeka. We use this setter
     * to store data until persisting it using the MediaWiki API client.
     *
     * @param string $wikitext
     * @param string $summary
     * @param bool $markComplete Mark the newest revision as complete?
     * @param bool $markApproved Mark the newest revision as approved?
     */
    public function setWikitextData($wikitext, $summary, $markComplete = false, $markApproved = false)
    {
        $this->wikitextData['wikitext'] = $wikitext;
        $this->wikitextData['summary'] = $summary;
        $this->wikitextData['mark_complete'] = (bool) $markComplete;
        $this->wikitextData['mark_approved'] = (bool) $markApproved;
    }

    /**
     * Get the Scripto media wikitext data.
     *
     * @param string $key
     * @return string|bool
     */
    public function getWikitextData($key)
    {
        return isset($this->wikitextData[$key]) ? $this->wikitextData[$key] : null;
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
     * future feature of reconstitution of projects should they be deleted.
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
