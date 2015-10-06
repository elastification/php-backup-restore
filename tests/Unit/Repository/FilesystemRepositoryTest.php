<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 06/10/15
 * Time: 08:07
 */

namespace Elastification\BackupRestore\Tests\Unit\Repository;

use Elastification\BackupRestore\Entity\Mappings;
use Elastification\BackupRestore\Entity\ServerInfo;
use Elastification\BackupRestore\Repository\FilesystemRepository;
use Elastification\BackupRestore\Repository\FilesystemRepositoryInterface;
use Symfony\Component\Finder\Finder;

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

    public function testStoreServerInfo()
    {
        $path = '/tmp/test-path';

        $filepath = $path .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::DIR_META .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::FILENAME_SERVER_INFO .
            FilesystemRepositoryInterface::FILE_EXTENSION;

        $serverInfo = new ServerInfo();
        $serverInfo->clusterName = 'my-cluster';
        $serverInfo->name = 'my-name';
        $serverInfo->version = '1.6.0';

        $this->filesystem->expects($this->once())->method('dumpFile')->with($filepath, json_encode($serverInfo));

        $this->filesystemRepository->storeServerInfo($path, $serverInfo);
    }

    public function testStoreRestoreServerInfoExistingFolder()
    {
        $path = '/tmp/test-path';
        $datetimeString = '20151006100005_';

        /** @var \PHPUnit_Framework_MockObject_MockObject $dateTime */
        $dateTime = $this->getMockBuilder('DateTime')->disableOriginalConstructor()->getMock();
        $dateTime->expects($this->once())->method('format')->with('YmdHis_')->willReturn($datetimeString);

        $folderpath = $path .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::DIR_META .
            DIRECTORY_SEPARATOR .
            $datetimeString .
            FilesystemRepositoryInterface::DIR_SUB_RESTORE;

        $filepath = $folderpath .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::FILENAME_SERVER_INFO .
            FilesystemRepositoryInterface::FILE_EXTENSION;

        $serverInfo = new ServerInfo();
        $serverInfo->clusterName = 'my-cluster';
        $serverInfo->name = 'my-name';
        $serverInfo->version = '1.6.0';

        $this->filesystem->expects($this->once())->method('exists')->with($folderpath)->willReturn(true);
        $this->filesystem->expects($this->never())->method('mkdir');
        $this->filesystem->expects($this->once())->method('dumpFile')->with($filepath, json_encode($serverInfo));

        $this->filesystemRepository->storeRestoreServerInfo($path, $dateTime, $serverInfo);
    }

    public function testStoreRestoreServerInfoNotExistingFolder()
    {
        $path = '/tmp/test-path';
        $datetimeString = '20151006100005_';

        /** @var \PHPUnit_Framework_MockObject_MockObject $dateTime */
        $dateTime = $this->getMockBuilder('DateTime')->disableOriginalConstructor()->getMock();
        $dateTime->expects($this->once())->method('format')->with('YmdHis_')->willReturn($datetimeString);

        $folderpath = $path .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::DIR_META .
            DIRECTORY_SEPARATOR .
            $datetimeString .
            FilesystemRepositoryInterface::DIR_SUB_RESTORE;

        $filepath = $folderpath .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::FILENAME_SERVER_INFO .
            FilesystemRepositoryInterface::FILE_EXTENSION;

        $serverInfo = new ServerInfo();
        $serverInfo->clusterName = 'my-cluster';
        $serverInfo->name = 'my-name';
        $serverInfo->version = '1.6.0';

        $this->filesystem->expects($this->once())->method('exists')->with($folderpath)->willReturn(false);
        $this->filesystem->expects($this->once())->method('mkdir')->with($folderpath);
        $this->filesystem->expects($this->once())->method('dumpFile')->with($filepath, json_encode($serverInfo));

        $this->filesystemRepository->storeRestoreServerInfo($path, $dateTime, $serverInfo);
    }

    public function testStoreRestoreStoredStatsExistingFolder()
    {
        $path = '/tmp/test-path';
        $datetimeString = '20151006100005_';

        /** @var \PHPUnit_Framework_MockObject_MockObject $dateTime */
        $dateTime = $this->getMockBuilder('DateTime')->disableOriginalConstructor()->getMock();
        $dateTime->expects($this->once())->method('format')->with('YmdHis_')->willReturn($datetimeString);

        $folderpath = $path .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::DIR_META .
            DIRECTORY_SEPARATOR .
            $datetimeString .
            FilesystemRepositoryInterface::DIR_SUB_RESTORE;

        $filepath = $folderpath .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::FILENAME_STORED_STATS .
            FilesystemRepositoryInterface::FILE_EXTENSION;

        $storedStats = [['properties' => []]];

        $this->filesystem->expects($this->once())->method('exists')->with($folderpath)->willReturn(true);
        $this->filesystem->expects($this->never())->method('mkdir');
        $this->filesystem->expects($this->once())->method('dumpFile')->with($filepath, json_encode($storedStats));

        $this->filesystemRepository->storeRestoreStoredStats($path, $dateTime, $storedStats);
    }

    public function testStoreRestoreStoredStatsNotExistingFolder()
    {
        $path = '/tmp/test-path';
        $datetimeString = '20151006100005_';

        /** @var \PHPUnit_Framework_MockObject_MockObject $dateTime */
        $dateTime = $this->getMockBuilder('DateTime')->disableOriginalConstructor()->getMock();
        $dateTime->expects($this->once())->method('format')->with('YmdHis_')->willReturn($datetimeString);

        $folderpath = $path .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::DIR_META .
            DIRECTORY_SEPARATOR .
            $datetimeString .
            FilesystemRepositoryInterface::DIR_SUB_RESTORE;

        $filepath = $folderpath .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::FILENAME_STORED_STATS .
            FilesystemRepositoryInterface::FILE_EXTENSION;

        $storedStats = [['properties' => []]];

        $this->filesystem->expects($this->once())->method('exists')->with($folderpath)->willReturn(false);
        $this->filesystem->expects($this->once())->method('mkdir')->with($folderpath);
        $this->filesystem->expects($this->once())->method('dumpFile')->with($filepath, json_encode($storedStats));

        $this->filesystemRepository->storeRestoreStoredStats($path, $dateTime, $storedStats);
    }

    public function testStoreStoredStats()
    {
        $path = '/tmp/test-path';

        $filepath = $path .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::DIR_META .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::FILENAME_STORED_STATS .
            FilesystemRepositoryInterface::FILE_EXTENSION;

        $storedStats = [['properties' => []]];

        $this->filesystem->expects($this->never())->method('exists');
        $this->filesystem->expects($this->never())->method('mkdir');
        $this->filesystem->expects($this->once())->method('dumpFile')->with($filepath, json_encode($storedStats));

        $this->filesystemRepository->storeStoredStats($path, $storedStats);
    }

    public function testStoreBackupJobStats()
    {
        $path = '/tmp/test-path';

        $filepath = $path .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::DIR_META .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::FILENAME_JOB_STATS .
            FilesystemRepositoryInterface::FILE_EXTENSION;

        $jobStatsArray = [['stats' => []]];

        /** @var \PHPUnit_Framework_MockObject_MockObject $jobStats */
        $jobStats = $this->getMockBuilder('Elastification\BackupRestore\Entity\JobStats')
            ->disableOriginalConstructor()
            ->getMock();

        $jobStats->expects($this->once())->method('toArray')->willReturn($jobStatsArray);
        $this->filesystem->expects($this->once())->method('dumpFile')->with($filepath, json_encode($jobStatsArray));

        $this->filesystemRepository->storeBackupJobStats($path, $jobStats);
    }

    public function testStoreRestoreJobStatsExistingFolder()
    {
        $path = '/tmp/test-path';
        $datetimeString = '20151006100005_';

        /** @var \PHPUnit_Framework_MockObject_MockObject $dateTime */
        $dateTime = $this->getMockBuilder('DateTime')->disableOriginalConstructor()->getMock();
        $dateTime->expects($this->once())->method('format')->with('YmdHis_')->willReturn($datetimeString);

        $jobStatsArray = [['stats' => []]];

        /** @var \PHPUnit_Framework_MockObject_MockObject $jobStats */
        $jobStats = $this->getMockBuilder('Elastification\BackupRestore\Entity\JobStats')
            ->disableOriginalConstructor()
            ->getMock();

        $jobStats->expects($this->once())->method('toArray')->willReturn($jobStatsArray);

        $folderpath = $path .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::DIR_META .
            DIRECTORY_SEPARATOR .
            $datetimeString .
            FilesystemRepositoryInterface::DIR_SUB_RESTORE;

        $filepath = $folderpath .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::FILENAME_JOB_STATS .
            FilesystemRepositoryInterface::FILE_EXTENSION;

        $this->filesystem->expects($this->once())->method('exists')->with($folderpath)->willReturn(true);
        $this->filesystem->expects($this->never())->method('mkdir');
        $this->filesystem->expects($this->once())->method('dumpFile')->with($filepath, json_encode($jobStatsArray));

        $this->filesystemRepository->storeRestoreJobStats($path, $jobStats, $dateTime);
    }

    public function testStoreRestoreJobStatsNotExistingFolder()
    {
        $path = '/tmp/test-path';
        $datetimeString = '20151006100005_';

        /** @var \PHPUnit_Framework_MockObject_MockObject $dateTime */
        $dateTime = $this->getMockBuilder('DateTime')->disableOriginalConstructor()->getMock();
        $dateTime->expects($this->once())->method('format')->with('YmdHis_')->willReturn($datetimeString);

        $jobStatsArray = [['stats' => []]];

        /** @var \PHPUnit_Framework_MockObject_MockObject $jobStats */
        $jobStats = $this->getMockBuilder('Elastification\BackupRestore\Entity\JobStats')
            ->disableOriginalConstructor()
            ->getMock();

        $jobStats->expects($this->once())->method('toArray')->willReturn($jobStatsArray);

        $folderpath = $path .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::DIR_META .
            DIRECTORY_SEPARATOR .
            $datetimeString .
            FilesystemRepositoryInterface::DIR_SUB_RESTORE;

        $filepath = $folderpath .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::FILENAME_JOB_STATS .
            FilesystemRepositoryInterface::FILE_EXTENSION;

        $this->filesystem->expects($this->once())->method('exists')->with($folderpath)->willReturn(false);
        $this->filesystem->expects($this->once())->method('mkdir')->with($folderpath);
        $this->filesystem->expects($this->once())->method('dumpFile')->with($filepath, json_encode($jobStatsArray));

        $this->filesystemRepository->storeRestoreJobStats($path, $jobStats, $dateTime);
    }

    public function testStoreBackupConfig()
    {
        $path = '/tmp/test-path';
        $data = ['yaml' => 'data'];
        $yamlString = 'YamlString';

        $filepath = $path .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::DIR_CONFIG .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::FILENAME_CONFIG_BACKUP .
            FilesystemRepositoryInterface::FILE_EXTENSION_CONFIG;

        $this->yamlDumper->expects($this->once())->method('dump')->with($data, 5)->willReturn($yamlString);
        $this->filesystem->expects($this->once())->method('dumpFile')->with($filepath, $yamlString);

        $this->filesystemRepository->storeBackupConfig($path, $data);
    }

    public function testStoreRestoreConfig()
    {
        $path = '/tmp/test-path';
        $data = ['yaml' => 'data'];
        $yamlString = 'YamlString';
        $configName = 'my-config-name';

        $restoreJob = $this->getMockBuilder('Elastification\BackupRestore\Entity\RestoreJob')
            ->disableOriginalConstructor()
            ->getMock();

        $filepath = $path .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::DIR_CONFIG .
            DIRECTORY_SEPARATOR .
            $configName .
            FilesystemRepositoryInterface::FILE_EXTENSION_CONFIG;

        $restoreJob->expects($this->once())->method('getPath')->willReturn($path);
        $restoreJob->expects($this->once())->method('getConfigName')->willReturn($configName);
        $this->yamlDumper->expects($this->once())->method('dump')->with($data, 5)->willReturn($yamlString);
        $this->filesystem->expects($this->once())->method('dumpFile')->with($filepath, $yamlString);

        $this->filesystemRepository->storeRestoreConfig($restoreJob, $data);
    }

    public function testSymlinkLatestBackupNotExisting()
    {
        $path = '/tmp/test-path';
        $latestPath = dirname($path) . DIRECTORY_SEPARATOR . FilesystemRepositoryInterface::SYMLINK_LATEST;

        $this->filesystem->expects($this->once())->method('exists')->with($latestPath)->willReturn(false);
        $this->filesystem->expects($this->never())->method('remove');
        $this->filesystem->expects($this->once())->method('symlink')->with($path, $latestPath);

        $this->filesystemRepository->symlinkLatestBackup($path);
    }

    public function testSymlinkLatestBackupExisting()
    {
        $path = '/tmp/test-path';
        $latestPath = dirname($path) . DIRECTORY_SEPARATOR . FilesystemRepositoryInterface::SYMLINK_LATEST;

        $this->filesystem->expects($this->once())->method('exists')->with($latestPath)->willReturn(true);
        $this->filesystem->expects($this->once())->method('remove')->with($latestPath);
        $this->filesystem->expects($this->once())->method('symlink')->with($path, $latestPath);

        $this->filesystemRepository->symlinkLatestBackup($path);
    }

    public function testLoadYamlConfigException()
    {
        $path = '/tmp/test-path';
        $this->filesystem->expects($this->once())->method('exists')->with($path)->willReturn(false);

        try {
            $this->filesystemRepository->loadYamlConfig($path);
        } catch(\Exception $exception) {
            $this->assertEquals('Config file ' . $path . ' does not exist.', $exception->getMessage());
            return;
        }

        $this->fail();
    }

    public function testLoadYamlConfig()
    {
        $filepath = FIXTURE_ROOT . DIRECTORY_SEPARATOR . 'Unit' . DIRECTORY_SEPARATOR . 'dummy.yml';
        $parsed = ['parsed' => 'stuff'];
        $yamlContent = file_get_contents($filepath);

        $this->filesystem->expects($this->once())->method('exists')->with($filepath)->willReturn(true);
        $this->yamlParser->expects($this->once())->method('parse')->with($yamlContent)->willReturn($parsed);

        $result = $this->filesystemRepository->loadYamlConfig($filepath);
        $this->assertSame($parsed, $result);
    }
    public function testLoadMappingsException()
    {
        $path = '/tmp/test-path';
        $schemaFolderPath = $path . DIRECTORY_SEPARATOR . FilesystemRepositoryInterface::DIR_SCHEMA;

        $this->filesystem->expects($this->once())->method('exists')->with($schemaFolderPath)->willReturn(false);

        try {
            $this->filesystemRepository->loadMappings($path);
        } catch(\Exception $exception) {
            $this->assertEquals('Schema folder does not exist in ' . $path, $exception->getMessage());
            return;
        }

        $this->fail();
    }

    public function testLoadMappings()
    {
        $path = FIXTURE_ROOT . DIRECTORY_SEPARATOR . 'Unit';
        $schemaFolderPath = $path . DIRECTORY_SEPARATOR . FilesystemRepositoryInterface::DIR_SCHEMA;

        $types = ['type1', 'type2'];
        $type1Content = file_get_contents($schemaFolderPath . DIRECTORY_SEPARATOR . 'index1' . DIRECTORY_SEPARATOR . 'type1.json');
        $type2Content = file_get_contents($schemaFolderPath . DIRECTORY_SEPARATOR . 'index1' . DIRECTORY_SEPARATOR . 'type2.json');

        $this->filesystem->expects($this->once())->method('exists')->with($schemaFolderPath)->willReturn(true);

        $filesystemRepository = new FilesystemRepository(
            $this->filesystem,
            $this->yamlDumper,
            $this->yamlParser,
            new Finder()
        );

        $mappings = $filesystemRepository->loadMappings($path);

        foreach($mappings->getIndices() as $index) {
            $this->assertSame('index1', $index->getName());

            foreach($index->getTypes() as $type) {
                $this->assertTrue(in_array($type->getName(), $types));

                if('type1' === $type->getName()) {
                    $this->assertEquals($type1Content, json_encode($type->getSchema()));
                } else {
                    $this->assertEquals($type2Content, json_encode($type->getSchema()));
                }
            }
        }
    }

    public function testLoadDataFiles()
    {
        $path = FIXTURE_ROOT . DIRECTORY_SEPARATOR . 'Unit';
        $index = 'index1';
        $type = 'type1';
        $dataPath = $path .
            DIRECTORY_SEPARATOR .
            FilesystemRepositoryInterface::DIR_DATA .
            DIRECTORY_SEPARATOR .
            $index .
            DIRECTORY_SEPARATOR .
            $type;

        $filesystemRepository = new FilesystemRepository(
            $this->filesystem,
            $this->yamlDumper,
            $this->yamlParser,
            new Finder()
        );

        $files = $filesystemRepository->loadDataFiles($path, $index, $type);

        $this->assertCount(2, $files);
        foreach($files as $file) {
            $this->assertContains($dataPath, $file->getPath());
        }
    }
}