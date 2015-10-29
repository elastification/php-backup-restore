<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 08/10/15
 * Time: 08:44
 */

namespace Elastification\BackupRestore\Repository\Elasticsearch;

use Elastification\Client\Serializer\SerializerInterface;

class RequestFactory implements RequestFactoryInterface
{
    private $namespace = 'Elastification\\Client\\Request\\V%sx\\%s';

    /**
     * @param null|string $namespacePattern
     */
    public function __construct($namespacePattern = null)
    {
        if(null !== $namespacePattern) {
            $this->namespace = $namespacePattern;
        }
    }

    /**
     * @inheritdoc
     */
    public function create($className, $elasticsearchVersion, $index, $type, SerializerInterface $serializer)
    {
        $requestClassName = $this->getRequestClass($className, $elasticsearchVersion);

        return new $requestClassName($index, $type, $serializer);
    }

    /**
     * Generates a class with correct version path and namespace
     *
     * @param string $className
     * @return string
     * @author Daniel Wendlandt
     */
    private function getRequestClass($className, $elasticsearchVersion)
    {
        $version = explode('.', $elasticsearchVersion);

        return sprintf($this->namespace, $version[0], $className);
    }
}