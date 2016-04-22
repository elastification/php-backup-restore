<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 06/10/15
 * Time: 08:07
 */

namespace Elastification\BackupRestore\Tests\Unit\Entity\Mapping;

use Elastification\BackupRestore\Entity\IndexTypeStats\Index;

class TypeTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Index
     */
    private $index;


    protected function setUp()
    {
        parent::setUp();

        $this->index = new Index();
    }

    protected function tearDown()
    {
        $this->index = null;

        parent::tearDown();
    }

    public function testInstance()
    {
        $this->assertInstanceOf('Elastification\BackupRestore\Entity\IndexTypeStats\Index', $this->index);
    }

    public function testGetSetName()
    {
        $name = 'my-name';

        $this->assertNull($this->index->getName());
        $this->index->setName($name);
        $this->assertSame($name, $this->index->getName());
    }

    public function testGetSetDocsInIndex()
    {
        $docsInIndex = 111;

        $this->assertSame(0, $this->index->getDocsInIndex());
        $this->index->setDocsInIndex($docsInIndex);
        $this->assertSame($docsInIndex, $this->index->getDocsInIndex());
    }

    public function testGetAddType()
    {
        $typeName = 'type-name';

        $type = $this->getMockBuilder('\Elastification\BackupRestore\Entity\IndexTypeStats\Type')
            ->disableOriginalConstructor()
            ->getMock();

        $type->expects($this->once())->method('getName')->willReturn($typeName);

        $this->assertTrue(is_array($this->index->getTypes()));
        $this->assertEmpty($this->index->getTypes());

        $this->index->addType($type);

        $expected = array($typeName => $type);

        $this->assertSame($expected, $this->index->getTypes());
    }

    public function testGetDocsInTypeMissingType()
    {
        $typeName = 'not-existing';

        $this->assertSame(0, $this->index->getDocsInType($typeName));
    }

    public function testGetDocsInType()
    {
        $typeName = 'type-name';
        $value = 22;

        $type = $this->getMockBuilder('\Elastification\BackupRestore\Entity\IndexTypeStats\Type')
            ->disableOriginalConstructor()
            ->getMock();

        $type->expects($this->once())->method('getName')->willReturn($typeName);
        $type->expects($this->once())->method('getDocsInType')->willReturn($value);

        $this->index->addType($type);

        $this->assertSame($value, $this->index->getDocsInType($typeName));
    }
}