<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 06/10/15
 * Time: 08:07
 */

namespace Elastification\BackupRestore\Tests\Unit\Entity;


use Elastification\BackupRestore\Entity\JobStats\SectionData;

class SectionDataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SectionData
     */
    private $sectionData;

    protected function setUp()
    {
        parent::setUp();

        $this->sectionData = new SectionData();
    }

    protected function tearDown()
    {
        $this->sectionData = null;

        parent::tearDown();
    }

    public function testInstance()
    {
        $this->assertInstanceOf('Elastification\BackupRestore\Entity\JobStats\SectionData', $this->sectionData);
    }

    public function testGetCreatedAt()
    {
        $this->assertInstanceOf('\DateTime', $this->sectionData->getCreatedAt());

        $dateTime = new \DateTime();
        $dateTime->add(new \DateInterval('P1M'));
        $this->assertTrue($dateTime > $this->sectionData->getCreatedAt());
    }

    public function testGetSetName()
    {
        $name = 'my-name';

        $this->assertNull($this->sectionData->getName());
        $this->sectionData->setName($name);
        $this->assertSame($name, $this->sectionData->getName());
    }

    public function testGetSetTimeTaken()
    {
        $timeTaken = 1700;

        $this->assertSame(0, $this->sectionData->getTimeTaken());
        $this->sectionData->setTimeTaken($timeTaken);
        $this->assertSame($timeTaken, $this->sectionData->getTimeTaken());
    }

    public function testGetSetMemoryUsage()
    {
        $memoryUsage = 1100000;

        $this->assertSame(0, $this->sectionData->getMemoryUsage());
        $this->sectionData->setMemoryUsage($memoryUsage);
        $this->assertSame($memoryUsage, $this->sectionData->getMemoryUsage());
    }

    public function testGetSetMemoryUsed()
    {
        $memoryUsed = 11000234;

        $this->assertSame(0, $this->sectionData->getMemoryUsed());
        $this->sectionData->setMemoryUsed($memoryUsed);
        $this->assertSame($memoryUsed, $this->sectionData->getMemoryUsed());
    }

    public function testGetSetOptions()
    {
        $options = ['my' => 'option'];

        $this->assertTrue(is_array($this->sectionData->getOptions()));
        $this->assertEmpty($this->sectionData->getOptions());
        $this->sectionData->setOptions($options);
        $this->assertSame($options, $this->sectionData->getOptions());
    }

    public function testToArray()
    {
        $name = 'my-name';
        $timeTaken = 1700;
        $memoryUsage = 1100000;
        $memoryUsed = 11000234;
        $options = ['my' => 'option'];

        $arrayData = array(
            'name' => $name,
            'timeTaken' => $timeTaken,
            'memoryUsage' => $memoryUsage,
            'memoryUsed' => $memoryUsed,
            'options' => $options,
            'createdAt' => $this->sectionData->getCreatedAt()->format('Y-m-d H:i:s')
        );

        $this->sectionData->setName($name);
        $this->sectionData->setTimeTaken($timeTaken);
        $this->sectionData->setMemoryUsage($memoryUsage);
        $this->sectionData->setMemoryUsed($memoryUsed);
        $this->sectionData->setOptions($options);

        $this->assertSame($arrayData, $this->sectionData->toArray());
    }

}