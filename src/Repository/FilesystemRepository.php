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

class FilesystemRepository
{
    const DIR_META = 'meta';
    const DIR_SCHEMA = 'schema';
    const DIR_DATA = 'data';

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

    }

    public function storeDocument()
    {

    }
}

