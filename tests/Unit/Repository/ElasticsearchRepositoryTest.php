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
        $this->response->expects($this->exactly(3))->method('getData')->willReturn($data);

        $this->client->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf('Elastification\Client\Request\V1x\NodeInfoRequest'))
            ->willReturn($this->response);

        $serverInfo = $this->repository->getServerInfo(self::HOST, self::PORT);

        $this->assertInstanceOf('Elastification\BackupRestore\Entity\ServerInfo', $serverInfo);
        $this->assertSame($data['name'], $serverInfo->name);
        $this->assertSame($data['cluster_name'], $serverInfo->clusterName);
        $this->assertSame($data['version']['number'], $serverInfo->version);
    }

}