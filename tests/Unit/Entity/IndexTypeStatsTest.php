<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 06/10/15
 * Time: 08:07
 */

namespace Elastification\BackupRestore\Tests\Unit\Entity;

use Elastification\BackupRestore\Entity\IndexTypeStats;

class IndexTypeStatsTest extends \PHPUnit_Framework_TestCase
{

    const INDEX = 'my-index';
    const TYPE = 'my-type';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $index;

    /**
     * @var IndexTypeStats
     */
    private $stats;

    protected function setUp()
    {
        parent::setUp();

        $this->index = $this->getMockBuilder('\Elastification\BackupRestore\Entity\IndexTypeStats\Index')
            ->disableOriginalConstructor()
            ->getMock();

        $this->stats = new IndexTypeStats();
    }

    protected function tearDown()
    {
        $this->index = null;
        $this->stats = null;

        parent::tearDown();
    }

    public function testInstance()
    {
        $this->assertInstanceOf('Elastification\BackupRestore\Entity\IndexTypeStats', $this->stats);
    }

    public function testAddIndex()
    {
        $this->index->expects($this->once())->method('getName')->willReturn(self::INDEX);
        $this->stats->addIndex($this->index);

        $indices = $this->stats->getIndices();
        $this->assertCount(1, $indices);
        $this->assertArrayHasKey(self::INDEX, $indices);
        $this->assertSame($this->index, $indices[self::INDEX]);
    }

    public function testsGetIndicesEmpty()
    {
        $this->assertEmpty($this->stats->getIndices());
    }

    public function testsGetDocCountEmpty()
    {
        $this->assertSame(0, $this->stats->getDocCount(self::INDEX, self::TYPE));
    }

    public function testDocCountWithIndexOnly()
    {
        $count = 77;
        $this->index->expects($this->once())->method('getName')->willReturn(self::INDEX);
        $this->index->expects($this->once())->method('getDocsInIndex')->willReturn($count);
        $this->stats->addIndex($this->index);

        $result = $this->stats->getDocCount(self::INDEX);
        $this->assertSame($count, $result);

    }

    public function testDocCountWithIndexAndType()
    {
        $count = 11;
        $this->index->expects($this->once())->method('getName')->willReturn(self::INDEX);
        $this->index->expects($this->never())->method('getDocsInIndex');
        $this->index->expects($this->once())->method('getDocsInType')->with(self::TYPE)->willReturn($count);
        $this->stats->addIndex($this->index);

        $result = $this->stats->getDocCount(self::INDEX, self::TYPE);
        $this->assertSame($count, $result);
    }

}