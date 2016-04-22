<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 06/10/15
 * Time: 08:07
 */

namespace Elastification\BackupRestore\Tests\Unit\Entity\Mapping;


use Elastification\BackupRestore\Entity\IndexTypeStats\Type;

class TypeTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Type
     */
    private $type;


    protected function setUp()
    {
        parent::setUp();

        $this->type = new Type();
    }

    protected function tearDown()
    {
        $this->type = null;

        parent::tearDown();
    }

    public function testInstance()
    {
        $this->assertInstanceOf('Elastification\BackupRestore\Entity\IndexTypeStats\Type', $this->type);
    }

    public function testGetSetName()
    {
        $name = 'my-name';

        $this->assertNull($this->type->getName());
        $this->type->setName($name);
        $this->assertSame($name, $this->type->getName());
    }

    public function testGetSetDocsInType()
    {
        $docsInType = 555;

        $this->assertSame(0, $this->type->getDocsInType());
        $this->type->setDocsInType($docsInType);
        $this->assertSame($docsInType, $this->type->getDocsInType());
    }
}