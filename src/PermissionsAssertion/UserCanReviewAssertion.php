<?php
namespace Scripto\PermissionsAssertion;

use Omeka\Entity\User;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Assertion\AssertionInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

/**
 * Assert that an Omeka user can review a Scripto media.
 */
class UserCanReviewAssertion implements AssertionInterface
{
    public function assert(Acl $acl, RoleInterface $role = null,
        ResourceInterface $resource = null, $privilege = null
    ) {
        $project = $resource->getScriptoItem()->getScriptoProject();
        // The $reviewers collection is indexed by user_id.
        return $project->getReviewers()->containsKey($role->getId());
    }
}
