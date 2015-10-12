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

interface RequestFactoryInterface
{
    /**
     * Creates a new instancae of a request
     *
     * @param string $className
     * @param string $elasticsearchVersion
     * @param string $index
     * @param string $type
     * @param SerializerInterface $serializer
     * @return RequestInterface
     * @author Daniel Wendlandt
     */
    public function create($className, $elasticsearchVersion, $index, $type, SerializerInterface $serializer);
}

