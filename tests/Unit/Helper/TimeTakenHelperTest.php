<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 06/10/15
 * Time: 08:07
 */

namespace Elastification\BackupRestore\Tests\Unit\Helper;

use Elastification\BackupRestore\Helper\TimeTakenHelper;

class TimeTakenHelperTest extends \PHPUnit_Framework_TestCase
{

    public function testIsVersionAllowed()
    {
        $map = [
            ['value' => 10, 'expected' => '10.000 s'],
            ['value' => 60, 'expected' => '1.000 m'],
            ['value' => 60 * 60, 'expected' => '1.000 h'],
        ];

        foreach($map as $test) {
            $this->assertSame($test['expected'], TimeTakenHelper::convert($test['value']));
        }
    }

}