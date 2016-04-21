<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 06/10/15
 * Time: 08:07
 */

namespace Elastification\BackupRestore\Tests\Unit\Entity;

use Elastification\BackupRestore\Entity\Mappings;

class MappingsTest extends \PHPUnit_Framework_TestCase
{

    const INDEX = 'my-index';
    const TYPE = 'my-type';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $index;

    /**
     * @var Mappings
     */
    private $mappings;

    protected function setUp()
    {
        parent::setUp();

        $this->index = $this->getMockBuilder('\Elastification\BackupRestore\Entity\Mappings\Index')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mappings = new Mappings();
    }

    protected function tearDown()
    {
        $this->index = null;
        $this->mappings = null;

        parent::tearDown();
    }

    public function testInstance()
    {
        $this->assertInstanceOf('Elastification\BackupRestore\Entity\Mappings', $this->mappings);
    }

    public function testGetIndices()
    {
        $this->assertTrue(is_array($this->mappings->getIndices()));
        $this->assertEmpty($this->mappings->getIndices());
    }

    public function testAddIndex()
    {
        $this->mappings->addIndex($this->index);

        $this->assertTrue(is_array($this->mappings->getIndices()));
        $indices = $this->mappings->getIndices();
        $this->assertCount(1, $indices);
        $this->assertSame($this->index, $indices[0]);
    }

    public function testSetIndices()
    {
        $this->mappings->setIndices(array($this->index, $this->index));

        $this->assertTrue(is_array($this->mappings->getIndices()));
        $indices = $this->mappings->getIndices();
        $this->assertCount(2, $indices);
        $this->assertSame($this->index, $indices[0]);
        $this->assertSame($this->index, $indices[1]);
    }

    public function testCountIndices()
    {
        $this->assertSame(0, $this->mappings->countIndices());

        $this->mappings->setIndices(array($this->index, $this->index));

        $this->assertSame(2, $this->mappings->countIndices());
    }

    public function testCountTypes()
    {
        $countPerType = 3;
        $this->assertSame(0, $this->mappings->countTypes());

        $this->index->expects($this->exactly(2))->method('countTypes')->willReturn($countPerType);
        $this->mappings->setIndices(array($this->index, $this->index));

        $this->assertSame($countPerType + $countPerType, $this->mappings->countTypes());
    }

    public function testReduceIndicesWithEmpty()
    {
        $this->mappings->addIndex($this->index);

        $this->assertSame(1, $this->mappings->countIndices());
        $this->mappings->reduceIndices([]);
        $this->assertSame(1, $this->mappings->countIndices());
    }

    public function testReduceIndicesGivenArray()
    {
        $indices = [['index' => self::INDEX, 'type' => self::TYPE]];

        $type = $this->getMockBuilder('\Elastification\BackupRestore\Entity\Mappings\Type')
            ->disableOriginalConstructor()
            ->getMock();

        $type->expects($this->once())->method('getName')->willReturn(self::TYPE);

        $this->index->expects($this->once())->method('getName')->willReturn(self::INDEX);
        $this->index->expects($this->once())->method('getTypes')->willReturn([self::TYPE => $type]);
        $this->index->expects($this->once())->method('countTypes')->willReturn(0);

        $this->mappings->addIndex($this->index);

        $this->assertSame(1, $this->mappings->countIndices());
        $this->mappings->reduceIndices($indices);
        $this->assertSame(0, $this->mappings->countIndices());
    }

    public function testReduceIndicesGivenArrayWithoutAnyMatch()
    {
        $indices = [['index' => self::INDEX . '-fake', 'type' => 'notExisting']];

        $type = $this->getMockBuilder('\Elastification\BackupRestore\Entity\Mappings\Type')
            ->disableOriginalConstructor()
            ->getMock();

        $type->expects($this->once())->method('getName')->willReturn(self::TYPE);

        $this->index->expects($this->once())->method('getName')->willReturn(self::INDEX);
        $this->index->expects($this->once())->method('getTypes')->willReturn([self::TYPE => $type]);
        $this->index->expects($this->once())->method('countTypes')->willReturn(0);

        $this->mappings->addIndex($this->index);

        $this->assertSame(1, $this->mappings->countIndices());
        $this->mappings->reduceIndices($indices);
        $this->assertSame(0, $this->mappings->countIndices());
    }
}