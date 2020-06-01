<?php
namespace Scripto\Service\Form\Element;

use Scripto\Form\Element\MediaTypeSelect;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class MediaTypeSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $list = $this->listMediaTypes($services);

        $element = new MediaTypeSelect;
        return $element
            ->setValueOptions($list)
            ->setEmptyOption('Select media type…'); // @translate
    }

    protected function listMediaTypes(ContainerInterface $services)
    {
        $connection = $services->get('Omeka\Connection');
        $sql = <<<SQL
SELECT DISTINCT(media_type)
FROM media
WHERE media_type IS NOT NULL
AND media_type != ""
ORDER BY media_type
SQL;
        $stmt = $connection->query($sql);
        $result = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        return array_combine($result, $result);
    }
}
