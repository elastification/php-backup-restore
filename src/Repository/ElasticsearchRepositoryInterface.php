<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 15/09/15
 * Time: 14:21
 */
namespace Elastification\BackupRestore\Repository;

use Elastification\BackupRestore\Entity\IndexTypeStats;
use Elastification\BackupRestore\Entity\Mappings;
use Elastification\BackupRestore\Entity\ServerInfo;
use Elastification\Client\ClientInterface;
use Elastification\Client\Serializer\SerializerInterface;

interface ElasticsearchRepositoryInterface
{

    /**
     * Sets a client.
     *
     * @param ClientInterface $client
     * @param string $host
     * @param int $port
     * @author Daniel Wendlandt
     */
    public function setClient(ClientInterface $client, $host, $port = 9200);

    /**
     * Sets the serializer the will be used for requests
     *
     * @param SerializerInterface $serializer
     * @author Daniel Wendlandt
     */
    public function setSerializer(SerializerInterface $serializer);

    /**
     * Gets the server info for the current host
     * 
     * @param string $host
     * @param int $port
     * @return ServerInfo
     * @author Daniel Wendlandt
     */
    public function getServerInfo($host, $port = 9200);

    /**
     * Checks for number documents in all indices/types
     *
     * @param string $host
     * @param int $port
     * @param int $numberOfIndices
     * @return IndexTypeStats
     * @throws \Exception
     * @author Daniel Wendlandt
     */
    public function getDocCountByIndexType($host, $port = 9200, $numberOfIndices = 10000);

    /**
     * Get mappings for all indices
     *
     * @param string $host
     * @param int $port
     * @return Mappings
     * @throws \Exception
     * @author Daniel Wendlandt
     */
    public function getAllMappings($host, $port = 9200);

    /**
     * Starts a scroll search without sorting.
     * Size controls the number of results per shard
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-scroll.html
     *
     * @param string $index
     * @param string $type
     * @param string $host
     * @param int $port
     * @param string $scrollTimeUnit
     * @param int $sizePerChart
     * @return string
     * @throws \Exception
     * @author Daniel Wendlandt
     */
    public function createScrollSearch($index, $type, $host, $port = 9200, $scrollTimeUnit = '10m', $sizePerChart = 50);

    /**
     * Fetches data from started scroll search
     *
     * @param string $scrollId
     * @param string $host
     * @param int $port
     * @param string $scrollTimeUnit
     * @return array
     * @author Daniel Wendlandt
     */
    public function getScrollSearchData($scrollId, $host, $port = 9200, $scrollTimeUnit = '10m');

    /**
     * Creates a mapping for given index and type
     *
     * @param string $index
     * @param string $type
     * @param array $schema
     * @param $host
     * @param int $port
     * @author Daniel Wendlandt
     */
    public function createMapping($index, $type, array $schema, $host, $port = 9200);

    /**
     * Creates a new document or updates existing one.
     *
     * @param string $index
     * @param string $type
     * @param string $id
     * @param array $doc
     * @param string $host
     * @param int $port
     * @return \Elastification\Client\Response\ResponseInterface
     * @throws \Elastification\Client\Exception\RequestException
     * @author Daniel Wendlandt
     */
    public function createDocument($index, $type, $id, array $doc, $host, $port = 9200);

    /**
     * Refreshes an index
     *
     * @param string $index
     * @param string $host
     * @param int $port
     * @return \Elastification\Client\Response\ResponseInterface
     * @author Daniel Wendlandt
     */
    public function refreshIndex($index, $host, $port = 9200);

    /**
     * @param $index
     * @param $host
     * @param int $port
     * @return \Elastification\Client\Response\ResponseInterface
     * @author Dmitry Grachikov
     */
    public function getIndexSettings($index, $host, $port = 9200);

    /**
     * @param $index
     * @param array $settings
     * @param $host
     * @param int $port
     * @return \Elastification\Client\Response\ResponseInterface
     * @author Dmitry Grachikov
     */
    public function updateIndexSettings($index, array $settings, $host, $port = 9200);
}

