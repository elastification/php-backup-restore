<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 06/10/15
 * Time: 08:07
 */

namespace Elastification\BackupRestore\Tests\Unit\Repository;

use Elastification\BackupRestore\Entity\Mappings;
use Elastification\BackupRestore\Repository\FilesystemRepository;
use Elastification\BackupRestore\Repository\FilesystemRepositoryInterface;

class FilesystemRepositoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $filesystem;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $yamlParser;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $yamlDumper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $finder;

    /**
     * @var FilesystemRepositoryInterface
     */
    private $filesystemRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->filesystem = $this->getMockBuilder('Symfony\Component\Filesystem\Filesystem')->getMock();
        $this->yamlParser = $this->getMockBuilder('Symfony\Component\Yaml\Parser')->getMock();
        $this->yamlDumper = $this->getMockBuilder('Symfony\Component\Yaml\Dumper')->getMock();
        $this->finder = $this->getMockBuilder('Symfony\Component\Finder\Finder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystemRepository = new FilesystemRepository(
            $this->filesystem,
            $this->yamlDumper,
            $this->yamlParser,
            $this->finder);
    }

    protected function tearDown()
    {
        $this->filesystem = null;
        $this->yamlParser = null;
        $this->yamlDumper = null;
        $this->finder = null;
        $this->filesystemRepository = null;

        parent::tearDown();
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            'Elastification\BackupRestore\Repository\FilesystemRepositoryInterface',
            $this->filesystemRepository);

        $this->assertInstanceOf(
            'Elastification\BackupRestore\Repository\FilesystemRepository',
            $this->filesystemRepository);
    }

    public function testCreateStructure()
    {
        $path = '/tmp/test-path';
        $structureArray = [
            $path . DIRECTORY_SEPARATOR . FilesystemRepositoryInterface::DIR_META,
            $path . DIRECTORY_SEPARATOR . FilesystemRepositoryInterface::DIR_SCHEMA,
            $path . DIRECTORY_SEPARATOR . FilesystemRepositoryInterface::DIR_DATA
        ];

        $this->filesystem->expects($this->once())->method('mkdir')->with($structureArray);

        $this->filesystemRepository->createStructure($path);
    }

    public function testStoreMappingsEmpty()
    {
        $path = '/tmp/test-path';
        $mappings = new Mappings();

        $this->filesystem->expects($this->never())->method('mkdir');
        $this->filesystem->expects($this->never())->method('dumpFile');

        $createdFiles = $this->filesystemRepository->storeMappings($path, $mappings);

        $this->assertSame(0, $createdFiles);
    }

    public function testStoreMappings()
    {
        $path = '/tmp/test-path';
        $mappings = new Mappings();

        $type1 = new Mappings\Type();
        $type1->setName('type1');
        $type1->setSchema(array('properties' => array()));

        $type2 = new Mappings\Type();
        $type2->setName('type2');
        $type2->setSchema(array('properties' => array()));

        $index1 = new Mappings\Index();
        $index1->setName('index1');
        $index1->addType($type1);
        $index1->addType($type2);

        $mappings->addIndex($index1);

        $folderPath = $path . DIRECTORY_SEPARATOR . FilesystemRepositoryInterface::DIR_SCHEMA . DIRECTORY_SEPARATOR . $index1->getName();
        $schemaPath1 = $folderPath . DIRECTORY_SEPARATOR . $type1->getName() . FilesystemRepositoryInterface::FILE_EXTENSION;
        $schemaPath2 = $folderPath . DIRECTORY_SEPARATOR . $type2->getName() . FilesystemRepositoryInterface::FILE_EXTENSION;

        $this->filesystem->expects($this->once())->method('mkdir')->with($folderPath);
        $this->filesystem->expects($this->exactly(2))->method('dumpFile')->withConsecutive(
            array($schemaPath1, json_encode($type1->getSchema())),
            array($schemaPath2, json_encode($type2->getSchema()))
        );

        $createdFiles = $this->filesystemRepository->storeMappings($path, $mappings);

        $this->assertSame(2, $createdFiles);
    }

    public function testStoreDataEmptyFolderExisting()
    {
        $path = '/tmp/test-path';
        $index = 'my-index';
        $type = 'my-type';
        $docs = [];

        $folderPath = $path .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::DIR_DATA .
            DIRECTORY_SEPARATOR .
            $index .
            DIRECTORY_SEPARATOR .
            $type;

        $this->filesystem->expects($this->once())->method('exists')->with($folderPath)->willReturn(true);
        $this->filesystem->expects($this->never())->method('mkdir');
        $this->filesystem->expects($this->never())->method('dumpFile');

        $docsCreated = $this->filesystemRepository->storeData($path, $index, $type, $docs);

        $this->assertSame(0, $docsCreated);
    }

    public function testStoreDataEmptyFolderNotExisting()
    {
        $path = '/tmp/test-path';
        $index = 'my-index';
        $type = 'my-type';
        $docs = [];

        $folderPath = $path .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::DIR_DATA .
            DIRECTORY_SEPARATOR .
            $index .
            DIRECTORY_SEPARATOR .
            $type;

        $this->filesystem->expects($this->once())->method('exists')->with($folderPath)->willReturn(false);
        $this->filesystem->expects($this->once())->method('mkdir')->with($folderPath);
        $this->filesystem->expects($this->never())->method('dumpFile');

        $docsCreated = $this->filesystemRepository->storeData($path, $index, $type, $docs);

        $this->assertSame(0, $docsCreated);
    }

    public function testStoreDataFolderExisting()
    {
        $path = '/tmp/test-path';
        $index = 'my-index';
        $type = 'my-type';
        $docs = [
            ['_id' => 'F3jhso8', '_source' => []],
            ['_id' => 'KI3jhso8mb4n3asd23ku', '_source' => []]];

        $folderPath = $path .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::DIR_DATA .
            DIRECTORY_SEPARATOR .
            $index .
            DIRECTORY_SEPARATOR .
            $type;

        $filepathDoc1 = $folderPath .
                DIRECTORY_SEPARATOR .
                substr($docs[0]['_id'], 0, 2) .
                DIRECTORY_SEPARATOR .
                $docs[0]['_id'] .
                FilesystemRepositoryInterface::FILE_EXTENSION;

        $filepathDoc2 = $folderPath .
            DIRECTORY_SEPARATOR .
            substr($docs[1]['_id'], 0, 2) .
            DIRECTORY_SEPARATOR .
            $docs[1]['_id'] .
            FilesystemRepositoryInterface::FILE_EXTENSION;

        $this->filesystem->expects($this->once())->method('exists')->with($folderPath)->willReturn(true);
        $this->filesystem->expects($this->never())->method('mkdir');
        $this->filesystem->expects($this->exactly(2))->method('dumpFile')
            ->withConsecutive(
                array($filepathDoc1, json_encode($docs[0])),
                array($filepathDoc2, json_encode($docs[1]))
            );

        $docsCreated = $this->filesystemRepository->storeData($path, $index, $type, $docs);

        $this->assertSame(2, $docsCreated);
    }
}