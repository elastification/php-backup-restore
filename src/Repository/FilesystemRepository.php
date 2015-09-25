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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;

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
     * @var Parser
     */
    private $yamlParser;

    /**
     * @var Finder
     */
    private $finder;


    /**
     * @param Filesystem|null $filesystem
     * @param Dumper $yamlDumper
     * @param Parser $yamlParser
     * @param Finder $finder
     */
    public function __construct(
        Filesystem $filesystem = null,
        Dumper $yamlDumper = null,
        Parser $yamlParser = null,
        Finder $finder = null)
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

        if(null === $yamlParser) {
            $this->yamlParser = new Parser();
        } else {
            $this->yamlParser = $yamlParser;
        }

        if(null === $finder) {
            $this->finder = new Finder();
        } else {
            $this->yamlParser = $finder;
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
    public function storeBackupJobStats($path, JobStats $jobStats)
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
     * Stores job statistics as json to file
     *
     * @param string $path
     * @param JobStats $jobStats
     * @author Daniel Wendlandt
     */
    public function storeRestoreJobStats($path, JobStats $jobStats)
    {
        $folderpath = $path .
            DIRECTORY_SEPARATOR .
            self::DIR_META .
            DIRECTORY_SEPARATOR .
            self::DIR_SUB_RESTORE;

        $filepath = $folderpath .
            DIRECTORY_SEPARATOR .
            self::FILENAME_JOB_STATS .
            self::FILE_EXTENSION;

        if(!$this->filesytsem->exists($folderpath)) {
            $this->filesytsem->mkdir($folderpath);
        }

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

    /**
     * Loads a file and parses the yaml content into an array
     *
     * @param string $filepath
     * @return array
     * @throws \Exception
     * @author Daniel Wendlandt
     */
    public function loadYamlConfig($filepath)
    {
        if(!$this->filesytsem->exists($filepath)) {
            throw new \Exception('Config file ' . $filepath . ' does not exist.');
        }

        $yamlString = file_get_contents($filepath);

        return $this->yamlParser->parse($yamlString);
    }

    /**
     * Loads the mappings that are located in the filesystem of stored backup
     *
     * @param string $filepath
     * @return Mappings
     * @throws \Exception
     * @author Daniel Wendlandt
     */
    public function loadMappings($filepath)
    {
        $schemaFolderPath = $filepath . DIRECTORY_SEPARATOR . self::DIR_SCHEMA;

        if(!$this->filesytsem->exists($schemaFolderPath)) {
            throw new \Exception('Schema folder does not exist in ' . $filepath);
        }

        /** @var Finder $finder */
        $finder = new $this->finder;

        $indices = array();
        /** @var SplFileInfo $file */
        foreach($finder->files()->in($schemaFolderPath)->name('*' . self::FILE_EXTENSION) as $file) {
            $indexName = $file->getRelativePath();

            if(!isset($indices[$indexName])) {
                $index = new Mappings\Index();
                $index->setName($indexName);

                $indices[$indexName] = $index;
            }

            /** @var Mappings\Index $index */
            $index = $indices[$indexName];

            //perform type;
            $type = new Mappings\Type();
            $type->setName($file->getBasename(self::FILE_EXTENSION));
            $type->setSchema(json_decode($file->getContents(), true));

            $index->addType($type);
        }

        $mappings = new Mappings();
        $mappings->setIndices($indices);

        return $mappings;
    }

    /**
     * Loads all files for a index/type
     *
     * @param string $path
     * @param string $index
     * @param string $type
     * @return null|Finder
     * @author Daniel Wendlandt
     */
    public function loadDataFiles($path, $index, $type)
    {
        /** @var Finder $finder */
        $finder = new $this->finder;

        $dataPath = $path .
            DIRECTORY_SEPARATOR .
            self::DIR_DATA .
            DIRECTORY_SEPARATOR .
            $index .
            DIRECTORY_SEPARATOR .
            $type;

        try {
            return $finder->files()->in($dataPath)->name('*' . self::FILE_EXTENSION);
        } catch(\InvalidArgumentException $exception) {
            return null;
        }

    }
}

