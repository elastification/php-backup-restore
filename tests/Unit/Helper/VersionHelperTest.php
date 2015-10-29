<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 06/10/15
 * Time: 08:07
 */

namespace Elastification\BackupRestore\Tests\Unit\Helper;

use Elastification\BackupRestore\Helper\VersionHelper;

class VersionHelperTest extends \PHPUnit_Framework_TestCase
{

    public function testIsVersionAllowed()
    {
        $this->assertTrue(VersionHelper::isVersionAllowed('1.7.3'));
        $this->assertFalse(VersionHelper::isVersionAllowed('0.90.3'));
        $this->assertFalse(VersionHelper::isVersionAllowed('2.0.3'));
        $this->assertFalse(VersionHelper::isVersionAllowed('v1.0.3'));
    }

}