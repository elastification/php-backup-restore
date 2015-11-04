<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 06/10/15
 * Time: 08:07
 */

namespace Elastification\BackupRestore\Tests\Unit\Entity;


use Elastification\BackupRestore\Entity\JobStats;
use Elastification\BackupRestore\Entity\ServerInfo;

class JobStatsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JobStats
     */
    private $entity;

    protected function setUp()
    {
        parent::setUp();

        $this->entity = new JobStats();
    }

    protected function tearDown()
    {
        $this->entity = null;

        parent::tearDown();
    }

    public function testInstance()
    {
        $this->assertInstanceOf('Elastification\BackupRestore\Entity\JobStats', $this->entity);
    }

    public function testTimeTaken()
    {
        $timeTaken = 55;
        $this->assertSame(0, $this->entity->getTimeTaken());
        $this->entity->setTimeTaken($timeTaken);
        $this->assertSame($timeTaken, $this->entity->getTimeTaken());
    }

    public function testMemoryUsage()
    {
        $memoryUsage = 11;
        $this->assertSame(0, $this->entity->getMemoryUsage());
        $this->entity->setMemoryUsage($memoryUsage);
        $this->assertSame($memoryUsage, $this->entity->getMemoryUsage());
    }

    public function testMemoryUsageReal()
    {
        $memoryUsageReal = 15;
        $this->assertSame(0, $this->entity->getMemoryUsageReal());
        $this->entity->setMemoryUsageReal($memoryUsageReal);
        $this->assertSame($memoryUsageReal, $this->entity->getMemoryUsageReal());
    }

    public function testMemoryUsed()
    {
        $memoryUsed = 11;
        $this->assertSame(0, $this->entity->getMemoryUsed());
        $this->entity->setMemoryUsed($memoryUsed);
        $this->assertSame($memoryUsed, $this->entity->getMemoryUsed());
    }

    public function testToArray()
    {
        $timeTaken = 55;
        $memoryUsage = 11;
        $memoryUsageReal = 15;
        $memoryUsed = 11;

        $this->entity->setTimeTaken($timeTaken);
        $this->entity->setMemoryUsage($memoryUsage);
        $this->entity->setMemoryUsageReal($memoryUsageReal);
        $this->entity->setMemoryUsed($memoryUsed);

        $result = $this->entity->toArray();
        $this->assertSame($timeTaken, $result['timeTaken']);
        $this->assertSame($memoryUsage, $result['memoryUsage']);
        $this->assertSame($memoryUsageReal, $result['memoryUsageReal']);
        $this->assertSame($memoryUsed, $result['memoryUsed']);
        $this->assertTrue(is_array($result['data']));
        $this->assertEmpty($result['data']);
        $this->assertContains(date('Y-m-d H:i:'), $result['createdAt']);
    }

    public function testSetCreateStructure()
    {
        $timeTaken = 11;
        $memoryUsage = 22;
        $memoryUsed = 33;
        $options = array('my' => 'option');

        $this->entity->setCreateStructure($timeTaken, $memoryUsage, $memoryUsed, $options);

        $result = $this->entity->toArray();
        $this->assertArrayHasKey(JobStats::NAME_CREATE_STRUCTURE, $result['data']);
        $data = $result['data'][JobStats::NAME_CREATE_STRUCTURE];
        $this->assertSame($timeTaken, $data['timeTaken']);
        $this->assertSame($memoryUsage, $data['memoryUsage']);
        $this->assertSame($memoryUsed, $data['memoryUsed']);
        $this->assertEquals($options, $data['options']);
        $this->assertContains(date('Y-m-d H:i:'), $data['createdAt']);
    }

    public function testSetStoreMappings()
    {
        $timeTaken = 11;
        $memoryUsage = 22;
        $memoryUsed = 33;
        $options = array('my' => 'option');

        $this->entity->setStoreMappings($timeTaken, $memoryUsage, $memoryUsed, $options);

        $result = $this->entity->toArray();
        $this->assertArrayHasKey(JobStats::NAME_STORE_MAPPINGS, $result['data']);
        $data = $result['data'][JobStats::NAME_STORE_MAPPINGS];
        $this->assertSame($timeTaken, $data['timeTaken']);
        $this->assertSame($memoryUsage, $data['memoryUsage']);
        $this->assertSame($memoryUsed, $data['memoryUsed']);
        $this->assertEquals($options, $data['options']);
        $this->assertContains(date('Y-m-d H:i:'), $data['createdAt']);
    }

    public function testSetStoreData()
    {
        $timeTaken = 11;
        $memoryUsage = 22;
        $memoryUsed = 33;
        $options = array('my' => 'option');

        $this->entity->setStoreData($timeTaken, $memoryUsage, $memoryUsed, $options);

        $result = $this->entity->toArray();
        $this->assertArrayHasKey(JobStats::NAME_STORE_DATA, $result['data']);
        $data = $result['data'][JobStats::NAME_STORE_DATA];
        $this->assertSame($timeTaken, $data['timeTaken']);
        $this->assertSame($memoryUsage, $data['memoryUsage']);
        $this->assertSame($memoryUsed, $data['memoryUsed']);
        $this->assertEquals($options, $data['options']);
        $this->assertContains(date('Y-m-d H:i:'), $data['createdAt']);
    }

    public function testSetStoreMetaData()
    {
        $timeTaken = 11;
        $memoryUsage = 22;
        $memoryUsed = 33;
        $options = array('my' => 'option');

        $this->entity->setStoreMetaData($timeTaken, $memoryUsage, $memoryUsed, $options);

        $result = $this->entity->toArray();
        $this->assertArrayHasKey(JobStats::NAME_STORE_META_DATA, $result['data']);
        $data = $result['data'][JobStats::NAME_STORE_META_DATA];
        $this->assertSame($timeTaken, $data['timeTaken']);
        $this->assertSame($memoryUsage, $data['memoryUsage']);
        $this->assertSame($memoryUsed, $data['memoryUsed']);
        $this->assertEquals($options, $data['options']);
        $this->assertContains(date('Y-m-d H:i:'), $data['createdAt']);
    }

    public function testSetRestoreHandleMappings()
    {
        $timeTaken = 11;
        $memoryUsage = 22;
        $memoryUsed = 33;
        $options = array('my' => 'option');

        $this->entity->setRestoreHandleMappings($timeTaken, $memoryUsage, $memoryUsed, $options);

        $result = $this->entity->toArray();
        $this->assertArrayHasKey(JobStats::NAME_RESTORE_HANDLE_MAPPINGS, $result['data']);
        $data = $result['data'][JobStats::NAME_RESTORE_HANDLE_MAPPINGS];
        $this->assertSame($timeTaken, $data['timeTaken']);
        $this->assertSame($memoryUsage, $data['memoryUsage']);
        $this->assertSame($memoryUsed, $data['memoryUsed']);
        $this->assertEquals($options, $data['options']);
        $this->assertContains(date('Y-m-d H:i:'), $data['createdAt']);
    }

    public function testSetRestoreData()
    {
        $timeTaken = 11;
        $memoryUsage = 22;
        $memoryUsed = 33;
        $options = array('my' => 'option');

        $this->entity->setRestoreData($timeTaken, $memoryUsage, $memoryUsed, $options);

        $result = $this->entity->toArray();
        $this->assertArrayHasKey(JobStats::NAME_RESTORE_DATA, $result['data']);
        $data = $result['data'][JobStats::NAME_RESTORE_DATA];
        $this->assertSame($timeTaken, $data['timeTaken']);
        $this->assertSame($memoryUsage, $data['memoryUsage']);
        $this->assertSame($memoryUsed, $data['memoryUsed']);
        $this->assertEquals($options, $data['options']);
        $this->assertContains(date('Y-m-d H:i:'), $data['createdAt']);
    }


}