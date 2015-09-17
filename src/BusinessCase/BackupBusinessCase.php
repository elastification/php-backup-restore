<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 16/09/15
 * Time: 18:18
 */

namespace Elastification\BackupRestore\BusinessCase;

use Elastification\BackupRestore\Entity\BackupJob;
use Elastification\BackupRestore\Entity\JobStats;
use Elastification\BackupRestore\Helper\VersionHelper;
use Elastification\BackupRestore\Repository\ElasticsearchRepository;
use Elastification\BackupRestore\Repository\ElasticsearchRepositoryInterface;
use Elastification\BackupRestore\Repository\FilesystemRepository;
use Elastification\BackupRestore\Repository\FilesystemRepositoryInterface;

class BackupBusinessCase
{
    /**
     * @var ElasticsearchRepositoryInterface
     */
    private $elastic;

    /**
     * @var FilesystemRepositoryInterface
     */
    private $filesystem;

    public function __construct(
        ElasticsearchRepositoryInterface $elastic = null,
        FilesystemRepositoryInterface $filesystem = null
    ) {
        if(null === $elastic) {
            $this->elastic = new ElasticsearchRepository();
        }

        if(null === $filesystem) {
            $this->filesystem = new FilesystemRepository();
        }
    }

    public function createJob($target, $host, $port = 9200, array $mappings = array())
    {
        $backupJob = new BackupJob();
        $backupJob->setHost($host);
        $backupJob->setPort($port);
        $backupJob->setTarget($target);
        $backupJob->setServerInfo($this->elastic->getServerInfo($host, $port));

        if(empty($mappings)) {
            $backupJob->setMappings($this->elastic->getAllMappings($host, $port));
        } else {
            throw new \Exception('custom types not implemented yet');
        }

        if(!VersionHelper::isVersionAllowed($backupJob->getServerInfo()->version)) {
            throw new \Exception('Elasticsearch version ' .
                $backupJob->getServerInfo()->version .
                ' is not supported by this tool');
        }

        return $backupJob;
    }

    public function execute(BackupJob $job)
    {
        $memoryAtStart = memory_get_usage();
        $timeStart = microtime(true);

        $jobStats = new JobStats();

        //SECTION create_structure
        $memoryAtSection = memory_get_usage();
        $timeStartSection = microtime(true);

        $this->filesystem->createStructure($job->getPath());

        $jobStats->setCreateStructure(
            microtime(true) - $timeStartSection,
            memory_get_usage(),
            memory_get_usage() - $memoryAtSection);


        //SECTION store_mappings
        $memoryAtSection = memory_get_usage();
        $timeStartSection = microtime(true);

        $mappingFilesCreated = $this->filesystem->storeMappings($job->getPath(), $job->getMappings());

        $jobStats->setStoreMappings(
            microtime(true) - $timeStartSection,
            memory_get_usage(),
            memory_get_usage() - $memoryAtSection,
            array('mappingFilesCreated' => $mappingFilesCreated));


        //global stuff
        $this->filesystem->symlinkLatestBackup($job->getPath());

        $jobStats->setTimeTaken(microtime(true) - $timeStart);
        $jobStats->setMemoryUsed(memory_get_usage() - $memoryAtStart);
        $jobStats->setMemoryUsage(memory_get_usage());
        $jobStats->setMemoryUsageReal(memory_get_usage(true));

        var_dump($jobStats);

    }







}