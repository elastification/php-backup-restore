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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Dumper;

class FilesystemRepository implements FilesystemRepositoryInterface
{

    /**
     * @var Filesystem
     */
    private $filesytsem;

    /**
     * @var Dumper
     */
    private $yamlDumper;

    /**
     * @param Filesystem|null $filesystem
     * @param Dumper $yamlDumper
     */
    public function __construct(Filesystem $filesystem = null, Dumper $yamlDumper = null)
    {
        if(null === $filesystem) {
            $this->filesytsem = new Filesystem();
        } else {
            $this->filesytsem = $filesystem;
        }

        if(null === $yamlDumper) {
            $this->yamlDumper = new Dumper();
        } else {
            $this->yamlDumper = $yamlDumper;
        }
    }

    /**
     * Creates the backup structure for a given path/job
     *
     * @param string $path
     * @author Daniel Wendlandt
     */
    public function createStructure($path)
    {
        $this->filesytsem->mkdir(array(
            $path . DIRECTORY_SEPARATOR . self::DIR_META,
            $path . DIRECTORY_SEPARATOR . self::DIR_SCHEMA,
            $path . DIRECTORY_SEPARATOR . self::DIR_DATA
        ));
    }

    /**
     * Stores all mappings. Returns the number of mappings that were stored
     *
     * @param string $path
     * @param Mappings $mappings
     * @return int
     * @author Daniel Wendlandt
     */
    public function storeMappings($path, Mappings $mappings)
    {
        $filesCreated = 0;

        /** @var Mappings\Index $index */
        foreach($mappings->getIndices() as $index) {
            $indexFolderPath = $path . DIRECTORY_SEPARATOR . self::DIR_SCHEMA . DIRECTORY_SEPARATOR . $index->getName();
            $this->filesytsem->mkdir($indexFolderPath);

            /** @var Mappings\Type $type */
            foreach($index->getTypes() as $type) {
                $schemaPath = $indexFolderPath . DIRECTORY_SEPARATOR . $type->getName() . self::FILE_EXTENSION;

                $this->filesytsem->dumpFile($schemaPath, json_encode($type->getSchema()));
                $filesCreated++;
            }
        }

        return $filesCreated;
    }

    /**
     * Stores complete doc result (all fields like: _id, _source) into json file
     * structure: data/index/type/[first two docId chars]/_id.json
     *
     * @param string $path
     * @param string $index
     * @param string $type
     * @param array $docs
     * @return int
     * @author Daniel Wendlandt
     */
    public function storeData($path, $index, $type, array $docs)
    {
        $folderPath = $path .
            DIRECTORY_SEPARATOR .
            self::DIR_DATA .
            DIRECTORY_SEPARATOR .
            $index .
            DIRECTORY_SEPARATOR .
            $type;

        if(!$this->filesytsem->exists($folderPath)) {
            $this->filesytsem->mkdir($folderPath);
        }

        $docsCreated = 0;
        foreach($docs as $doc) {
            $filePath = $folderPath .
                DIRECTORY_SEPARATOR .
                substr($doc['_id'], 0, 2) .
                DIRECTORY_SEPARATOR .
                $doc['_id'] .
                self::FILE_EXTENSION;

            $this->filesytsem->dumpFile($filePath, json_encode($doc));

            $docsCreated++;
        }

        return $docsCreated;
    }

    /**
     * Stores server info as json
     *
     * @param string $path
     * @param ServerInfo $serverInfo
     * @author Daniel Wendlandt
     */
    public function storeServerInfo($path, ServerInfo $serverInfo)
    {
        $filepath = $path .
            DIRECTORY_SEPARATOR .
            self::DIR_META .
            DIRECTORY_SEPARATOR .
            self::FILENAME_SERVER_INFO .
            self::FILE_EXTENSION;

        $this->filesytsem->dumpFile($filepath, json_encode($serverInfo));
    }

    /**
     * Stores processed backup job stats
     *
     * @param string $path
     * @param array $storedStats
     * @author Daniel Wendlandt
     */
    public function storeStoredStats($path, array $storedStats)
    {
        $filepath = $path .
            DIRECTORY_SEPARATOR .
            self::DIR_META .
            DIRECTORY_SEPARATOR .
            self::FILENAME_STORED_STATS .
            self::FILE_EXTENSION;

        $this->filesytsem->dumpFile($filepath, json_encode($storedStats));
    }

    /**
     * Stores job statistics as json to file
     *
     * @param string $path
     * @param JobStats $jobStats
     * @author Daniel Wendlandt
     */
    public function storeJobStats($path, JobStats $jobStats)
    {
        $filepath = $path .
            DIRECTORY_SEPARATOR .
            self::DIR_META .
            DIRECTORY_SEPARATOR .
            self::FILENAME_JOB_STATS .
            self::FILE_EXTENSION;

        $this->filesytsem->dumpFile($filepath, json_encode($jobStats->toArray()));
    }

    /**
     * Stores the backup config as yml
     *
     * @param string $filepath
     * @param array $data
     * @author Daniel Wendlandt
     */
    public function storeBackupConfig($filepath, array $data)
    {
        $filepath = $filepath .
            DIRECTORY_SEPARATOR .
            self::DIR_CONFIG .
            DIRECTORY_SEPARATOR .
            self::FILENAME_CONFIG_BACKUP .
            self::FILE_EXTENSION_CONFIG;

        $this->filesytsem->dumpFile($filepath, $this->yamlDumper->dump($data, 5));
    }

    /**
     * Symlink given path to latest in file system
     *
     * @param string $path
     * @author Daniel Wendlandt
     */
    public function symlinkLatestBackup($path)
    {
        $latestPath = dirname($path) . DIRECTORY_SEPARATOR . self::SYMLINK_LATEST;

        if($this->filesytsem->exists($latestPath)) {
            $this->filesytsem->remove($latestPath);
        }

        $this->filesytsem->symlink($path, $latestPath);
    }
}

