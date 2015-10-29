<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 06/10/15
 * Time: 08:07
 */

namespace Elastification\BackupRestore\Tests\Unit\Repository\Elasticsearch;

use Elastification\BackupRestore\Repository\Elasticsearch\RequestFactory;
use Elastification\BackupRestore\Repository\Elasticsearch\RequestFactoryInterface;
use Elastification\BackupRestore\Tests\Fixtures\Unit\DummyClass\V1x\DummyRequestClass;

class RequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    const NAMESPACE_PATTERN = 'Elastification\BackupRestore\Tests\Fixtures\Unit\DummyClass\V%sx\%s';

    /**
     * @var RequestFactoryInterface
     */
    private $factory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $serializer;

    protected function setUp()
    {
        parent::setUp();

        $this->serializer = $this->getMockBuilder('Elastification\Client\Serializer\SerializerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory = new RequestFactory(self::NAMESPACE_PATTERN);
    }

    protected function tearDown()
    {
        $this->factory = null;
        $this->serializer = null;

        parent::tearDown();
    }

    public function testInstance()
    {
        $this->assertInstanceOf(
            'Elastification\BackupRestore\Repository\Elasticsearch\RequestFactoryInterface',
            $this->factory);

        $this->assertInstanceOf(
            'Elastification\BackupRestore\Repository\Elasticsearch\RequestFactory',
            $this->factory);
    }

    public function testCreate()
    {
        $version = '1.4.1';
        $index = 'my-index';
        $type = 'my-type';

        /** @var DummyRequestClass $result */
        $result = $this->factory->create('DummyRequestClass', $version,$index, $type, $this->serializer);

        $this->assertInstanceOf('\Elastification\BackupRestore\Tests\Fixtures\Unit\DummyClass\V1x\DummyRequestClass',
            $result);
        $this->assertSame($index, $result->getIndex());
        $this->assertSame($type, $result->getType());
        $this->assertSame($this->serializer, $result->getSerializer());
    }

}