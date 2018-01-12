<?php
namespace Scripto\Api\Adapter;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class ScriptoItemAdapter extends AbstractEntityAdapter
{
    public function getResourceName()
    {
        return 'scripto_items';
    }

    public function getRepresentationClass()
    {
        return 'Scripto\Api\Representation\ScriptoItemRepresentation';
    }

    public function getEntityClass()
    {
        return 'Scripto\Entity\ScriptoItem';
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        if (Request::CREATE === $request->getOperation()) {
            $data = $request->getContent();
            if (!isset($data['o-module-scripto:project']['o:id'])) {
                $errorStore->addError('o-module-scripto:project', 'A Scripto item must be assigned a Scripto project on creation.'); // @translate
            }
            if (!isset($data['o:item']['o:id'])) {
                $errorStore->addError('o:item', 'A Scripto item must be assigned an item on creation.'); // @translate
            }
        }
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        if (Request::CREATE === $request->getOperation()) {
            $data = $request->getContent();
            $project = $this->getAdapter('scripto_projects')->findEntity($data['o-module-scripto:project']['o:id']);
            $entity->setProject($project);
            $item = $this->getAdapter('items')->findEntity($data['o:item']['o:id']);
            $entity->setItem($item);
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (null === $entity->getProject()) {
            $errorStore->addError('o-module-scripto:project', 'A Scripto project must not be null'); // @translate
        }
        if (null === $entity->getItem()) {
            $errorStore->addError('o:item', 'An item must not be null'); // @translate
        }
    }
}
