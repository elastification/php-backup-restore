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

class FilesystemRepository implements FilesystemRepositoryInterface
{

    /**
     * @var Filesystem
     */
    private $filesytsem;

    public function __construct(Filesystem $filesystem = null)
    {
        if(null === $filesystem) {
            $this->filesytsem = new Filesystem();
        }
    }

    public function createStructure($path)
    {
        $this->filesytsem->mkdir(array(
            $path . DIRECTORY_SEPARATOR . self::DIR_META,
            $path . DIRECTORY_SEPARATOR . self::DIR_SCHEMA,
            $path . DIRECTORY_SEPARATOR . self::DIR_DATA
        ));
    }

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
     * structure: data/index/type/_id.json
     *
     * @param string $path
     * @param string $index
     * @param string $type
     * @param array $docs
     * @return int
     * @author Daniel Wendlandt
     */
    public function storeDocuments($path, $index, $type, array $docs)
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
            $this->filesytsem->dumpFile(
                $folderPath . DIRECTORY_SEPARATOR . $doc['_id'] . self::FILE_EXTENSION,
                json_encode($doc));

            $docsCreated++;
        }

        return $docsCreated;
    }

    public function symlinkLatestBackup($path)
    {
        $latestPath = dirname($path) . DIRECTORY_SEPARATOR . self::SYMLINK_LATEST;

        if($this->filesytsem->exists($latestPath)) {
            $this->filesytsem->remove($latestPath);
        }

        $this->filesytsem->symlink($path, $latestPath);
    }
}

