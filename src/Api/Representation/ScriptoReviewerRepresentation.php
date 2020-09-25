<?php
namespace Scripto\Api\Representation;

use Omeka\Api\Representation\AbstractRepresentation;
use Scripto\Entity\ScriptoReviewer;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ScriptoReviewerRepresentation extends AbstractRepresentation
{
    protected $scriptoReviewer;

    public function __construct(ScriptoReviewer $scriptoReviewer, ServiceLocatorInterface $services)
    {
        $this->setServiceLocator($services);
        $this->scriptoReviewer = $scriptoReviewer;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return [
            'o:user' => $this->user()->getReference(),
        ];
    }

    public function user()
    {
        return $this->getAdapter('users')
            ->getRepresentation($this->scriptoReviewer->getUser());
    }

    public function scriptoProject()
    {
        return $this->getAdapter('scripto_projects')
            ->getRepresentation($this->scriptoReviewer->getScriptoProject());
    }
}
