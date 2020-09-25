<?php
namespace Scripto\Service\Form\Element;

use Scripto\Form\Element\MediaTypeSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MediaTypeSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $connection = $services->get('Omeka\Connection');
        $sql = '
        SELECT DISTINCT(media_type)
        FROM media
        WHERE media_type IS NOT NULL
        AND media_type != ""
        ORDER BY media_type';
        $stmt = $connection->query($sql);
        $result = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $valueOptions = array_combine($result, $result);
        $element = new MediaTypeSelect;
        return $element->setValueOptions($valueOptions);
    }
}
