<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 15/09/15
 * Time: 14:21
 */
namespace Elastification\BackupRestore\Repository;

use Elastification\BackupRestore\Entity\IndexTypeStats;
use Elastification\BackupRestore\Entity\JobStats;
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
    const DIR_CONFIG = 'config';

    const SYMLINK_LATEST = 'latest';

    const FILENAME_SERVER_INFO = 'server-info';
    const FILENAME_STORED_STATS = 'stored-stats';
    const FILENAME_JOB_STATS = 'job-stats';
    const FILENAME_CONFIG_BACKUP = 'backup-cfg';

    const FILE_EXTENSION = '.json';
    const FILE_EXTENSION_CONFIG = '.yml';

    /**
     * Creates the backup structure for a given path/job
     *
     * @param string $path
     * @author Daniel Wendlandt
     */
    public function createStructure($path);

    /**
     * Stores all mappings. Returns the number of mappings that were stored
     *
     * @param string $path
     * @param Mappings $mappings
     * @return int
     * @author Daniel Wendlandt
     */
    public function storeMappings($path, Mappings $mappings);

    /**
     * Stores complete doc result (all fields like: _id, _source) into json file
     * structure: data/index/type/_id.json
     *
     * @param string $path
     * @param string $index
     * @param string $type
     * @param array $docs
     * @return int
     * @author Daniel Wendlandt
     */
    public function storeData($path, $index, $type, array $docs);

    /**
     * Stores server info as json
     *
     * @param string $path
     * @param ServerInfo $serverInfo
     * @author Daniel Wendlandt
     */
    public function storeServerInfo($path, ServerInfo $serverInfo);

    /**
     * Stores processed backup job stats
     *
     * @param string $path
     * @param array $storedStats
     * @author Daniel Wendlandt
     */
    public function storeStoredStats($path, array $storedStats);

    /**
     * Stores job statistics as json to file
     *
     * @param string $path
     * @param JobStats $jobStats
     * @author Daniel Wendlandt
     */
    public function storeJobStats($path, JobStats $jobStats);

    /**
     * Stores the backup config as yml
     * @param string $filepath
     * @param array $data
     * @author Daniel Wendlandt
     */
    public function storeBackupConfig($filepath, array $data);

    /**
     * Symlink given path to latest in file system
     *
     * @param string $path
     * @author Daniel Wendlandt
     */
    public function symlinkLatestBackup($path);

    /**
     * Loads a file and parses the yaml content into an array
     *
     * @param string $filepath
     * @return array
     * @throws \Exception
     * @author Daniel Wendlandt
     */
    public function loadYamlConfig($filepath);

}

