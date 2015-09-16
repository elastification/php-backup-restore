<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 16/09/15
 * Time: 08:42
 */

namespace Elastification\BackupRestore\Entity;

class ServerInfo
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $clusterName;

    /**
     * @var string
     */
    public $version;
}