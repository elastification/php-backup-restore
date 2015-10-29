<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 06/10/15
 * Time: 08:07
 */

namespace Elastification\BackupRestore\Tests\Unit\Entity;


use Elastification\BackupRestore\Entity\ServerInfo;

class ServerInfoTest extends \PHPUnit_Framework_TestCase
{

    public function testInstance()
    {
        $serverInfo = new ServerInfo();
        $this->assertInstanceOf('Elastification\BackupRestore\Entity\ServerInfo', $serverInfo);
    }

    public function testProperties()
    {
        $serverInfo = new ServerInfo();
        $properties = get_object_vars($serverInfo);

        $this->assertArrayHasKey('name', $properties);
        $this->assertArrayHasKey('clusterName', $properties);
        $this->assertArrayHasKey('version', $properties);
    }

}