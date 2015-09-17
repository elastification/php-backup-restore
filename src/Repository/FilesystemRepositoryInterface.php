<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 15/09/15
 * Time: 14:21
 */
namespace Elastification\BackupRestore\Repository;

use Elastification\BackupRestore\Entity\IndexTypeStats;
use Elastification\BackupRestore\Entity\Mappings;
use Elastification\BackupRestore\Entity\ServerInfo;
use Elastification\BackupRestore\Helper\VersionHelper;
use Elastification\BackupRestore\Repository\ElasticQuery\QueryInterface;
use Elastification\Client\Request\V1x\NodeInfoRequest;
use Elastification\Client\Request\V1x\SearchRequest;
use Elastification\Client\Response\V1x\NodeInfoResponse;
use Symfony\Component\Filesystem\Filesystem;

interface FilesystemRepositoryInterface
{
    const DIR_META = 'meta';
    const DIR_SCHEMA = 'schema';
    const DIR_DATA = 'data';

    const SYMLINK_LATEST = 'latest';

    const FILE_EXTENSION = '.json';

    public function createStructure($path);

    public function storeMappings($path, Mappings $mappings);

    public function storeDocument();

    public function symlinkLatestBackup($path);

}

