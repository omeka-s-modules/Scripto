<?php
namespace Scripto\PermissionsAssertion;

use Scripto\Entity\ScriptoItem;
use Scripto\Entity\ScriptoMedia;
use Scripto\Entity\ScriptoProject;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class ProjectIsPublicAssertion implements AssertionInterface
{
    public function assert(Acl $acl, RoleInterface $role = null,
        ResourceInterface $resource = null, $privilege = null
    ) {
        if ($resource instanceof ScriptoProject) {
            $project = $resource;
        } elseif ($resource instanceof ScriptoItem) {
            $project = $resource->getScriptoProject();
        } elseif ($resource instanceof ScriptoMedia) {
            $project = $resource->getScriptoItem()->getScriptoProject();
        } else {
            return false;
        }
        return $project->getIsPublic();
    }
}
