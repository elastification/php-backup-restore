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

}