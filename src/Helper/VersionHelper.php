<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 16/09/15
 * Time: 11:24
 */

namespace Elastification\BackupRestore\Helper;

abstract class VersionHelper
{
    public static function isVersionAllowed($version)
    {
        return preg_match('/^(1).*/', $version) > 0;
    }
}