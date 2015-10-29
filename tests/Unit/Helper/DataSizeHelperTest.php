<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 06/10/15
 * Time: 08:07
 */

namespace Elastification\BackupRestore\Tests\Unit\Helper;


use Elastification\BackupRestore\Helper\DataSizeHelper;

class DataSizeHelperTest extends \PHPUnit_Framework_TestCase
{

    public function testIsVersionAllowed()
    {
        $map = [
            ['value' => 1, 'expected' => '1 b'],
            ['value' => 1024, 'expected' => '1 kb'],
            ['value' => 1024 * 1024, 'expected' => '1 mb'],
            ['value' => 1024 * 1024 * 1024, 'expected' => '1 gb'],
            ['value' => 1024 * 1024 * 1024 * 1024, 'expected' => '1 tb'],
            ['value' => 1024 * 1024 * 1024 * 1024 * 1024, 'expected' => '1 pb']
        ];

        foreach($map as $test) {
            $this->assertSame($test['expected'], DataSizeHelper::convert($test['value']));
        }
    }

}