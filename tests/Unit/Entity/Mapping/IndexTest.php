<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 06/10/15
 * Time: 08:07
 */

namespace Elastification\BackupRestore\Tests\Unit\Entity\IndexTypeStats;


use Elastification\BackupRestore\Entity\Mappings\Index;

class IndexTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $type;

    /**
     * @var Index
     */
    private $index;


    protected function setUp()
    {
        parent::setUp();

        $this->type = $this->getMockBuilder('\Elastification\BackupRestore\Entity\Mappings\Type')
            ->disableOriginalConstructor()
            ->getMock();

        $this->index = new Index();
    }

    protected function tearDown()
    {
        $this->type = null;
        $this->index = null;

        parent::tearDown();
    }

    public function testInstance()
    {
        $this->assertInstanceOf('Elastification\BackupRestore\Entity\Mappings\Index', $this->index);
    }

    public function testGetSetName()
    {
        $name = 'my-name';

        $this->assertNull($this->index->getName());
        $this->index->setName($name);
        $this->assertSame($name, $this->index->getName());
    }

    public function testGetAddType()
    {
        $this->assertTrue(is_array($this->index->getTypes()));
        $this->assertEmpty($this->index->getTypes());
        $this->index->addType($this->type);
        $this->assertSame([$this->type], $this->index->getTypes());
    }

    public function testGetAddTypeMultiple()
    {
        $this->assertTrue(is_array($this->index->getTypes()));
        $this->assertEmpty($this->index->getTypes());
        $this->index->addType($this->type);
        $this->index->addType($this->type);
        $this->index->addType($this->type);
        $this->assertSame([$this->type, $this->type, $this->type], $this->index->getTypes());
    }

    public function testCountTypes()
    {
        $this->assertSame(0, $this->index->countTypes());
        $this->index->addType($this->type);
        $this->index->addType($this->type);
        $this->index->addType($this->type);
        $this->assertSame(3, $this->index->countTypes());
    }

    public function testRemoveTypeByIndex()
    {
        $this->assertSame(0, $this->index->countTypes());
        $this->index->addType($this->type);
        $this->assertSame(1, $this->index->countTypes());
        $this->index->removeTypeByIndex(0);
        $this->assertSame(0, $this->index->countTypes());
    }

    public function testRemoveTypeByIndexMultiple()
    {
        $this->assertSame(0, $this->index->countTypes());
        $this->index->addType($this->type);
        $this->index->addType($this->type);
        $this->index->addType($this->type);
        $this->assertSame(3, $this->index->countTypes());
        $this->index->removeTypeByIndex(0);
        $this->index->removeTypeByIndex(1);
        $this->assertSame(1, $this->index->countTypes());
    }


}