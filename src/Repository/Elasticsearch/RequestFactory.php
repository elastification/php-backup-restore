<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 08/10/15
 * Time: 08:44
 */

namespace Elastification\BackupRestore\Repository\Elasticsearch;

use Elastification\Client\Request\RequestInterface;
use Elastification\Client\Serializer\SerializerInterface;

class RequestFactory implements RequestFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function create($className, $elasticsearchVersion, $index, $type, SerializerInterface $serializer)
    {
        $requestClassName = $this->getRequestClass($className, $elasticsearchVersion);

        return new $requestClassName(null, null, $serializer);
    }

    /**
     * Generates a fully qualified classname for requests of elastification
     *
     * @param string $className
     * @return string
     * @author Daniel Wendlandt
     */
    private function getRequestClass($className, $elasticsearchVersion)
    {
        $namespace = 'Elastification\\Client\\Request\\V%sx\\%s';

        return $this->generateClassName($namespace, $className, $elasticsearchVersion);
    }

    /**
     * Generates a class with correct version path and namespace
     *
     * @param string $namespace
     * @param string $className
     * @return string
     * @author Daniel Wendlandt
     */
    private function generateClassName($namespace, $className, $elasticsearchVersion)
    {
        $version = explode('.', $elasticsearchVersion);

        return sprintf($namespace, $version[0], $className);
    }
}