<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 18/09/15
 * Time: 12:53
 */

namespace Elastification\BackupRestore\Helper;

abstract class DataSizeHelper
{
    /**
     * @var array
     */
    private static $units = array('b','kb','mb','gb','tb','pb');

    /**
     * Converts a size into string.
     *
     * @param int $size
     * @return string
     * @author Daniel Wendlandt
     */
    public static function convert($size)
    {
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.static::$units[$i];
    }
}