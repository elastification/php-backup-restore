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
    private $serverInfoResponse;

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
        $this->response = $this->getMockBuilder('Elastification\Client\Response\ResponseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->serverInfoResponse = $this->getMockBuilder('Elastification\Client\Response\ResponseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = new ElasticsearchRepository();
        $this->repository->setClient($this->client, self::HOST, self::PORT);
        $this->repository->setSerializer($this->serializer);
    }

    protected function tearDown()
    {
        $this->client = null;
        $this->serializer = null;
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
        $data = [
            'name' => 'my-name',
            'cluster_name' => 'my-cluster-name',
            'version' => ['number' => '1.6.0']
        ];

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
        $serverInfoData = [
            'name' => 'my-name',
            'cluster_name' => 'my-cluster-name',
            'version' => ['number' => '1.6.0']
        ];

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

        $this->serverInfoResponse->expects($this->exactly(3))->method('getData')->willReturn($serverInfoData);
        $this->response->expects($this->once())->method('getData')->willReturn($aggsData);

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
        $serverInfoData = [
            'name' => 'my-name',
            'cluster_name' => 'my-cluster-name',
            'version' => ['number' => '1.6.0']
        ];

        $mappingData = [];

        $this->serverInfoResponse->expects($this->exactly(3))->method('getData')->willReturn($serverInfoData);
        $this->response->expects($this->once())->method('getData')->willReturn($mappingData);

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
        $serverInfoData = [
            'name' => 'my-name',
            'cluster_name' => 'my-cluster-name',
            'version' => ['number' => '1.6.0']
        ];

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

}