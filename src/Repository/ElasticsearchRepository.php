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
use Elastification\BackupRestore\Helper\VersionHelper;
use Elastification\BackupRestore\Repository\ElasticQuery\QueryInterface;
use Elastification\Client\Exception\ClientException;
use Elastification\Client\Request\V1x\Index\CreateMappingRequest;
use Elastification\Client\Request\V1x\NodeInfoRequest;
use Elastification\Client\Request\V1x\SearchRequest;
use Elastification\Client\Request\V1x\SearchScrollRequest;
use Elastification\Client\Request\V1x\UpdateDocumentRequest;
use Elastification\Client\Response\V1x\NodeInfoResponse;
use Elastification\Client\Response\V1x\SearchResponse;

/**
 * Class ElasticsearchRepository
 * @package Elastification\BackupRestore\Repository
 * @author Daniel Wendlandt
 */
class ElasticsearchRepository extends AbstractElasticsearchRepository implements ElasticsearchRepositoryInterface
{

    /**
     * @var ServerInfo
     */
    private $serverInfo;

    /**
     * Gets the server info for the current host
     *
     * @param string $host
     * @param int $port
     * @return ServerInfo
     * @author Daniel Wendlandt
     */
    public function getServerInfo($host, $port = 9200)
    {
        $request = new NodeInfoRequest($this->getSerializer());
        $client = $this->getClient($host, $port);
        /** @var NodeInfoResponse $response */
        $response = $client->send($request);

        $serverInfo = new ServerInfo();
        $serverInfo->name = $response->getData()['name'];
        $serverInfo->clusterName = $response->getData()['cluster_name'];
        $serverInfo->version = $response->getData()['version']['number'];

        $this->serverInfo = $serverInfo;

        return $serverInfo;
    }

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
    public function getDocCountByIndexType($host, $port = 9200, $numberOfIndices = 10000)
    {
        $this->checkServerInfo($host, $port);
        $queryClassName = $this->getQueryClass('DocsInIndexTypeQuery');

        /** @var QueryInterface $query */
        $query = new $queryClassName();

        /** @var SearchRequest $request */
        $request = $this->requestFactory->create(
            'SearchRequest',
            $this->serverInfo->version,
            null,
            null,
            $this->getSerializer());
        $request->setBody($query->getBody(array('size' => $numberOfIndices)));

        $client = $this->getClient($host, $port);
        $response = $client->send($request);

        $indexTypeStats = new IndexTypeStats();
        foreach($response->getData()['aggregations']['count_docs_in_index']['buckets'] as $indexBucket) {
            $index = new IndexTypeStats\Index();
            $index->setName($indexBucket['key']);
            $index->setDocsInIndex($indexBucket['doc_count']);

            foreach($indexBucket['count_docs_in_types']['buckets'] as $typeBucket) {
                $type = new IndexTypeStats\Type();
                $type->setName($typeBucket['key']);
                $type->setDocsInType($typeBucket['doc_count']);

                $index->addType($type);
            }

            $indexTypeStats->addIndex($index);
        }

        return $indexTypeStats;
    }

    /**
     * Get mappings for all indices
     *
     * @param string $host
     * @param int $port
     * @return Mappings
     * @throws \Exception
     * @author Daniel Wendlandt
     */
    public function getAllMappings($host, $port = 9200)
    {
        $this->checkServerInfo($host, $port);

        $request = $this->requestFactory->create(
            'Index\\GetMappingRequest',
            $this->serverInfo->version,
            null,
            null,
            $this->getSerializer());

        $client = $this->getClient($host, $port);

        $response = $client->send($request);

        $mappings = new Mappings();
        foreach($response->getData() as $indexName => $typeMappings) {
            $index = new Mappings\Index();
            $index->setName($indexName);

            foreach($typeMappings['mappings'] as $typeName => $schema) {
                $type = new Mappings\Type();
                $type->setName($typeName);
                $type->setSchema($schema);

                $index->addType($type);
            }

            $mappings->addIndex($index);
        }

        return $mappings;
    }

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
    public function createScrollSearch($index, $type, $host, $port = 9200, $scrollTimeUnit = '10m', $sizePerChart = 50)
    {
        $this->checkServerInfo($host, $port);

        $query = array(
            'query' => array(
                'match_all' => array()
            )
        );

        /** @var SearchRequest $request */
        $request = $this->requestFactory->create(
            'SearchRequest',
            $this->serverInfo->version,
            $index,
            $type,
            $this->getSerializer());
        $request->setParameter('scroll', $scrollTimeUnit);
        $request->setParameter('size', $sizePerChart);
        $request->setParameter('search_type', 'scan');
        $request->setBody($query);

        $client = $this->getClient($host, $port);

        $response = $client->send($request);
        $data = $response->getData()->getGatewayValue();

        if(!isset($data['_scroll_id'])) {
            throw new \Exception('Scroll id is not set in in response');
        }

        return $data['_scroll_id'];
    }

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
    public function getScrollSearchData($scrollId, $host, $port = 9200, $scrollTimeUnit = '10m')
    {
        $this->checkServerInfo($host, $port);

        /** @var SearchScrollRequest $request */
        $request = $this->requestFactory->create(
            'SearchScrollRequest',
            $this->serverInfo->version,
            null,
            null,
            $this->getSerializer()
        );
        $request->setScroll($scrollTimeUnit);
        $request->setScrollId($scrollId);

        $client = $this->getClient($host, $port);
        /** @var SearchResponse $response */
        $response = $client->send($request);

        return array('scrollId' => $response->getData()['_scroll_id'], 'hits' => $response->getHitsHits());
    }

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
    public function createMapping($index, $type, array $schema, $host, $port = 9200)
    {
        $client = $this->getClient($host, $port);

        //delete existing one
        $deleteRequestClassName = $this->getRequestClass('Index\\DeleteIndexRequest');
        /** @var CreateMappingRequest $request */
        $deleteRequest = new $deleteRequestClassName($index, $type, $this->getSerializer());

        try {
            $client->send($deleteRequest);
        } catch(ClientException $exception) {
            //do nothing. this is planned
        }

        //check for index and create one
        $createRequestClassName = $this->getRequestClass('Index\\CreateIndexRequest');
        /** @var CreateMappingRequest $request */
        $createRequest = new $createRequestClassName($index, null, $this->getSerializer());

        try {
            $client->send($createRequest);
        } catch(ClientException $exception) {
            //do nothing. this is planned
        }

        //add new mapping
        $requestClassName = $this->getRequestClass('Index\\CreateMappingRequest');
        /** @var CreateMappingRequest $request */
        $request = new $requestClassName($index, $type, $this->getSerializer());
        $request->setBody($schema);

        $client->send($request);
    }

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
    public function createDocument($index, $type, $id, array $doc, $host, $port = 9200)
    {
        $client = $this->getClient($host, $port);

        $updateDocRequestClassName = $this->getRequestClass('UpdateDocumentRequest');
        /** @var UpdateDocumentRequest $updateDocRequest */
        $updateDocRequest = new $updateDocRequestClassName($index, $type, $this->getSerializer());

        $updateDocRequest->setId($id);
        $updateDocRequest->setBody($doc);

        return $client->send($updateDocRequest);
    }

    /**
     * Refreshes an index
     *
     * @param string $index
     * @param string $host
     * @param int $port
     * @return \Elastification\Client\Response\ResponseInterface
     * @author Daniel Wendlandt
     */
    public function refreshIndex($index, $host, $port = 9200)
    {
        $client = $this->getClient($host, $port);

        $refreshIndexRequestClassName = $this->getRequestClass('Index\\RefreshIndexRequest');
        $refreshIndexRequest = new $refreshIndexRequestClassName($index, null, $this->getSerializer());

        return $client->send($refreshIndexRequest);
    }

    /**
     * Checks if server info is set. If not it fetches the server info again.
     * Also a verison check is done, if elasticsearch version is supported by this software
     *
     * @param string $host
     * @param int $port
     * @throws \Exception
     * @author Daniel Wendlandt
     */
    private function checkServerInfo($host, $port = 9200)
    {
        if(null === $this->serverInfo) {
            $this->getServerInfo($host, $port);
        }

        if(!VersionHelper::isVersionAllowed($this->serverInfo->version)) {
            throw new \Exception('Elasticsearch version ' . $this->serverInfo->version . ' is not supported by this tool');
        }
    }

    /**
     * Generates a fully qualified classname for queries of elastification
     *
     * @param string $className
     * @return string
     * @author Daniel Wendlandt
     */
    private function getQueryClass($className)
    {
        $namespace = 'Elastification\\BackupRestore\\Repository\\ElasticQuery\\V%sx\\%s';

        return $this->generateClassName($namespace, $className);
    }

    /**
     * Generates a fully qualified classname for requests of elastification
     *
     * @param string $className
     * @return string
     * @author Daniel Wendlandt
     */
    private function getRequestClass($className)
    {
        $namespace = 'Elastification\\Client\\Request\\V%sx\\%s';

        return $this->generateClassName($namespace, $className);
    }

    /**
     * Generates a class with correct version path and namespace
     *
     * @param string $namespace
     * @param string $className
     * @return string
     * @author Daniel Wendlandt
     */
    private function generateClassName($namespace, $className)
    {
        $version = explode('.', $this->serverInfo->version);

        return sprintf($namespace, $version[0], $className);
    }
}

