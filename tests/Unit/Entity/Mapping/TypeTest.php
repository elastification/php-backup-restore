<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 06/10/15
 * Time: 08:07
 */

namespace Elastification\BackupRestore\Tests\Unit\Entity\Mapping;


use Elastification\BackupRestore\Entity\Mappings\Type;

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
        $this->assertInstanceOf('Elastification\BackupRestore\Entity\Mappings\Type', $this->type);
    }

    public function testGetSetName()
    {
        $name = 'my-name';

        $this->assertNull($this->type->getName());
        $this->type->setName($name);
        $this->assertSame($name, $this->type->getName());
    }

    public function testGetSetSchema()
    {
        $schema = array('hooray' => array('a' => 'new schema'));

        $this->assertTrue(is_array($this->type->getSchema()));
        $this->assertEmpty($this->type->getSchema());
        $this->type->setSchema($schema);
        $this->assertSame($schema, $this->type->getSchema());
    }

}