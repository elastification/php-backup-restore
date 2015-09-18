<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 18/09/15
 * Time: 12:53
 */

namespace Elastification\BackupRestore\Helper;

abstract class TimeTakenHelper
{
    /**
     * @var array
     */
    private static $units = array('s', 'm', 'h');

    public static function convert($secs)
    {
        $ret = $secs;
        $formatter = 0;

        if($secs >= (60 * 60)) {
            $formatter = 2;
            $ret = ($secs / 60) / 60;
        } else if($secs >= 60) {
            $formatter = 1;
            $ret = ($secs / 60);
        }

        $ret = number_format($ret,3,'.','') . ' ' . static::$units[$formatter];

        return $ret;
    }
}