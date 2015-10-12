<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 06/10/15
 * Time: 08:07
 */

namespace Elastification\BackupRestore\Tests\Unit\Repository;

use Elastification\BackupRestore\Entity\Mappings;
use Elastification\BackupRestore\Entity\ServerInfo;
use Elastification\BackupRestore\Repository\ElasticQuery\V1x\DocsInIndexTypeQuery;
use Elastification\BackupRestore\Repository\ElasticsearchRepository;
use Elastification\BackupRestore\Repository\ElasticsearchRepositoryInterface;
use Elastification\BackupRestore\Repository\FilesystemRepository;
use Elastification\BackupRestore\Repository\FilesystemRepositoryInterface;
use Symfony\Component\Finder\Finder;

class ElasticsearchRepositoryTest extends \PHPUnit_Framework_TestCase
{
    const HOST = 'my-host';
    const PORT = 9211;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $client;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $serializer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $response;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $serverInfoResponse;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $requestFactory;

    /**
     * @var ElasticsearchRepositoryInterface
     */
    private $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->client = $this->getMockBuilder('Elastification\Client\ClientInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->serializer = $this->getMockBuilder('Elastification\Client\Serializer\SerializerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder('Elastification\Client\Request\RequestInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->response = $this->getMockBuilder('Elastification\Client\Response\ResponseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->serverInfoResponse = $this->getMockBuilder('Elastification\Client\Response\ResponseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestFactory = $this->getMockBuilder('Elastification\BackupRestore\Repository\Elasticsearch\RequestFactoryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = new ElasticsearchRepository($this->requestFactory);
        $this->repository->setClient($this->client, self::HOST, self::PORT);
        $this->repository->setSerializer($this->serializer);
    }

    protected function tearDown()
    {
        $this->client = null;
        $this->serializer = null;
        $this->request = null;
        $this->response = null;
        $this->repository = null;
        $this->serverInfoResponse = null;

        parent::tearDown();
    }

    public function testInstance()
    {
        $this->assertInstanceOf(
            'Elastification\BackupRestore\Repository\ElasticsearchRepositoryInterface',
            $this->repository);

        $this->assertInstanceOf(
            'Elastification\BackupRestore\Repository\ElasticsearchRepository',
            $this->repository);

        $this->assertInstanceOf(
            'Elastification\BackupRestore\Repository\AbstractElasticsearchRepository',
            $this->repository);
    }

    public function testGetServerInfo()
    {
        $data = $this->getServerInfoData();

        $this->serializer->expects($this->never())->method('serialize');
        $this->serverInfoResponse->expects($this->exactly(3))->method('getData')->willReturn($data);

        $this->client->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf('Elastification\Client\Request\V1x\NodeInfoRequest'))
            ->willReturn($this->serverInfoResponse);

        $serverInfo = $this->repository->getServerInfo(self::HOST, self::PORT);

        $this->assertInstanceOf('Elastification\BackupRestore\Entity\ServerInfo', $serverInfo);
        $this->assertSame($data['name'], $serverInfo->name);
        $this->assertSame($data['cluster_name'], $serverInfo->clusterName);
        $this->assertSame($data['version']['number'], $serverInfo->version);
    }

    public function testGetDocCountByIndexType()
    {
        $version = '1.6.0';
        $serverInfoData = $this->getServerInfoData($version);

        $docCount = 88;
        $aggsData['aggregations']['count_docs_in_index']['buckets'] = [
            [
                'key' => 'my-index',
                'doc_count' => $docCount,
                'count_docs_in_types' => [
                    'buckets' => [
                        [
                            'key' => 'my-type',
                            'doc_count' => 44,
                        ]
                    ]
                ]
            ]
        ];
        $query = new DocsInIndexTypeQuery();

        $this->serverInfoResponse->expects($this->exactly(3))->method('getData')->willReturn($serverInfoData);
        $this->response->expects($this->once())->method('getData')->willReturn($aggsData);
        $this->request->expects($this->once())
            ->method('setBody')
            ->with($this->equalTo($query->getBody(array('size' => 10000))))
            ->willReturn(json_encode($aggsData));
        $this->requestFactory->expects($this->once())
            ->method('create')
            ->with('SearchRequest', $version, null, null, $this->serializer)
            ->willReturn($this->request);

        $this->client->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                $this->isInstanceOf('Elastification\Client\Request\V1x\NodeInfoRequest'),
                $this->isInstanceOf('Elastification\Client\Request\V1x\SearchRequest'))
            ->willReturnOnConsecutiveCalls(
                $this->serverInfoResponse,
                $this->response
            );

        $result = $this->repository->getDocCountByIndexType(self::HOST, self::PORT);

        $this->assertInstanceOf('Elastification\BackupRestore\Entity\IndexTypeStats', $result);
        $this->assertSame($docCount, $result->getDocCount('my-index'));
        $this->assertCount(1, $result->getIndices());
        $index = $result->getIndices()['my-index'];
        $this->assertSame('my-index', $index->getName());
        $this->assertCount(1, $index->getTypes());
        $this->assertSame($docCount, $index->getDocsInIndex());
        $type = $index->getTypes()['my-type'];
        $this->assertSame('my-type', $type->getName());
        $this->assertSame(44, $type->getDocsInType());
    }

    public function testGetAllMappingsEmpty()
    {
        $version = '1.6.0';
        $serverInfoData = $this->getServerInfoData($version);

        $mappingData = [];

        $this->serverInfoResponse->expects($this->exactly(3))->method('getData')->willReturn($serverInfoData);
        $this->response->expects($this->once())->method('getData')->willReturn($mappingData);
        $this->request->expects($this->never())->method('setBody');
        $this->requestFactory->expects($this->once())
            ->method('create')
            ->with('Index\\GetMappingRequest', $version, null, null, $this->serializer)
            ->willReturn($this->request);

        $this->client->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                $this->isInstanceOf('Elastification\Client\Request\V1x\NodeInfoRequest'),
                $this->isInstanceOf('Elastification\Client\Request\V1x\Index\GetMappingRequest'))
            ->willReturnOnConsecutiveCalls(
                $this->serverInfoResponse,
                $this->response
            );

        $result = $this->repository->getAllMappings(self::HOST, self::PORT);
        $this->assertInstanceOf('Elastification\BackupRestore\Entity\Mappings', $result);
    }

    public function testGetAllMappings()
    {
        $version = '1.6.0';
        $serverInfoData = $this->getServerInfoData($version);

        $mappingData = [
            'my-index' => [
                'mappings' => [
                    'my-type' => [
                        'properties' => []
                    ]
                ]
            ]
        ];

        $this->serverInfoResponse->expects($this->exactly(3))->method('getData')->willReturn($serverInfoData);
        $this->response->expects($this->once())->method('getData')->willReturn($mappingData);
        $this->request->expects($this->never())->method('setBody');
        $this->requestFactory->expects($this->once())
            ->method('create')
            ->with('Index\\GetMappingRequest', $version, null, null, $this->serializer)
            ->willReturn($this->request);

        $this->client->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                $this->isInstanceOf('Elastification\Client\Request\V1x\NodeInfoRequest'),
                $this->isInstanceOf('Elastification\Client\Request\V1x\Index\GetMappingRequest'))
            ->willReturnOnConsecutiveCalls(
                $this->serverInfoResponse,
                $this->response
            );

        $result = $this->repository->getAllMappings(self::HOST, self::PORT);
        $this->assertInstanceOf('Elastification\BackupRestore\Entity\Mappings', $result);
        $this->assertSame(1, $result->countIndices());
        $this->assertSame(1, $result->countTypes());
        $index = $result->getIndices()[0];
        $this->assertSame('my-index', $index->getName());
        $type = $index->getTypes()[0];
        $this->assertSame('my-type', $type->getName());
        $schema = $type->getSchema();
        $this->assertTrue(isset($schema['properties']));
    }

    public function testCreateScrollSearch()
    {
        $version = '1.6.0';
        $serverInfoData = $this->getServerInfoData($version);
        $query = array(
            'query' => array(
                'match_all' => array()
            )
        );
        $scrollUnitTimeout = '1m';
        $sizePerChart = 2;
        $index = 'my-index';
        $type = 'my-type';
        $data = ['_scroll_id' => 'abc'];

        $nativeArrayGatewaay = $this->getMockBuilder('Elastification\Client\Serializer\Gateway\NativeArrayGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $nativeArrayGatewaay->expects($this->once())->method('getGatewayValue')->willReturn($data);
        $this->response->expects($this->once())->method('getData')->willReturn($nativeArrayGatewaay);
        $this->serverInfoResponse->expects($this->exactly(3))->method('getData')->willReturn($serverInfoData);
        $this->request->expects($this->once())->method('setBody')->with($this->equalTo($query));

        $this->request->expects($this->exactly(3))->method('setParameter')->withConsecutive(
            array('scroll', $scrollUnitTimeout),
            array('size', $sizePerChart),
            array('search_type', 'scan')
        );

        $this->requestFactory->expects($this->once())
            ->method('create')
            ->with('SearchRequest', $version, $index, $type, $this->serializer)
            ->willReturn($this->request);

        $this->client->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                $this->isInstanceOf('Elastification\Client\Request\V1x\NodeInfoRequest'),
                $this->isInstanceOf('Elastification\Client\Request\V1x\SearchRequest'))
            ->willReturnOnConsecutiveCalls(
                $this->serverInfoResponse,
                $this->response
            );

        $result = $this->repository->createScrollSearch(
            $index,
            $type,
            self::HOST,
            self::PORT,
            $scrollUnitTimeout,
            $sizePerChart);

        $this->assertSame($data['_scroll_id'], $result);
    }

    public function testCreateScrollSearchException()
    {
        $version = '1.6.0';
        $serverInfoData = $this->getServerInfoData($version);
        $query = array(
            'query' => array(
                'match_all' => array()
            )
        );
        $scrollUnitTimeout = '1m';
        $sizePerChart = 2;
        $index = 'my-index';
        $type = 'my-type';
        $data = ['no_scroll_id' => 'abc'];

        $nativeArrayGatewaay = $this->getMockBuilder('Elastification\Client\Serializer\Gateway\NativeArrayGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $nativeArrayGatewaay->expects($this->once())->method('getGatewayValue')->willReturn($data);
        $this->response->expects($this->once())->method('getData')->willReturn($nativeArrayGatewaay);
        $this->serverInfoResponse->expects($this->exactly(3))->method('getData')->willReturn($serverInfoData);
        $this->request->expects($this->once())->method('setBody')->with($this->equalTo($query));

        $this->request->expects($this->exactly(3))->method('setParameter')->withConsecutive(
            array('scroll', $scrollUnitTimeout),
            array('size', $sizePerChart),
            array('search_type', 'scan')
        );

        $this->requestFactory->expects($this->once())
            ->method('create')
            ->with('SearchRequest', $version, $index, $type, $this->serializer)
            ->willReturn($this->request);

        $this->client->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                $this->isInstanceOf('Elastification\Client\Request\V1x\NodeInfoRequest'),
                $this->isInstanceOf('Elastification\Client\Request\V1x\SearchRequest'))
            ->willReturnOnConsecutiveCalls(
                $this->serverInfoResponse,
                $this->response
            );

        try {
            $this->repository->createScrollSearch(
                $index,
                $type,
                self::HOST,
                self::PORT,
                $scrollUnitTimeout,
                $sizePerChart);
        } catch(\Exception $exception) {
            $this->assertSame('Scroll id is not set in in response', $exception->getMessage());
            return;
        }

        $this->fail();
    }

    public function testGetScrollSearchData()
    {
        $version = '1.6.0';
        $serverInfoData = $this->getServerInfoData($version);
        $scrollId = 'abcdef';
        $scrollTimeUnit = '1m';
        $data = ['_scroll_id' => 'abc'];
        $hits = [['_source' => []]];

        $this->request = $this->getMockBuilder('Elastification\Client\Request\V1x\SearchScrollRequest')
            ->disableOriginalConstructor()
            ->getMock();

        $this->response = $this->getMockBuilder('Elastification\Client\Response\V1x\SearchResponse')
            ->disableOriginalConstructor()
            ->getMock();

        $this->serverInfoResponse->expects($this->exactly(3))->method('getData')->willReturn($serverInfoData);
        $this->request->expects($this->once())->method('setScroll')->with($scrollTimeUnit);
        $this->request->expects($this->once())->method('setScrollId')->with($scrollId);
        $this->response->expects($this->once())->method('getData')->willReturn($data);
        $this->response->expects($this->once())->method('getHitsHits')->willReturn($hits);

        $this->requestFactory->expects($this->once())
            ->method('create')
            ->with('SearchScrollRequest', $version, null, null, $this->serializer)
            ->willReturn($this->request);

        $this->client->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                $this->isInstanceOf('Elastification\Client\Request\V1x\NodeInfoRequest'),
                $this->isInstanceOf('Elastification\Client\Request\V1x\SearchRequest'))
            ->willReturnOnConsecutiveCalls(
                $this->serverInfoResponse,
                $this->response
            );

        $result = $this->repository->getScrollSearchData($scrollId, self::HOST, self::PORT, $scrollTimeUnit);
        $this->assertTrue(isset($result['scrollId']));
        $this->assertTrue(isset($result['hits']));
        $this->assertSame($data['_scroll_id'], $result['scrollId']);
        $this->assertSame($hits, $result['hits']);
    }

    /**
     * @param string $version
     * @return array
     * @author Daniel Wendlandt
     */
    private function getServerInfoData($version = '1.6.0')
    {
        return [
            'name' => 'my-name',
            'cluster_name' => 'my-cluster-name',
            'version' => ['number' => $version]
        ];
    }

}