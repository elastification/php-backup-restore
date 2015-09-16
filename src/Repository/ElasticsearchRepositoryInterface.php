<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 15/09/15
 * Time: 14:21
 */
namespace Elastification\BackupRestore\Repository;

use Elastification\BackupRestore\Entity\ServerInfo;

interface ElasticsearchRepositoryInterface
{

    /**
     * Gets the server info for the current host
     * 
     * @param string $host
     * @param int $port
     * @return ServerInfo
     * @author Daniel Wendlandt
     */
    public function getServerInfo($host, $port = 9200);

    /**
     * Checks for number documents in all indeces/types
     *
     * @param string $host
     * @param int $port
     * @return IndexTypeStats
     * @throws \Exception
     * @author Daniel Wendlandt
     */
    public function getDocCountByIndexType($host, $port = 9200);
}

