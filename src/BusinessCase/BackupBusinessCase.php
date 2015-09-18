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
use Elastification\BackupRestore\Entity\Mappings\Index;
use Elastification\BackupRestore\Entity\Mappings\Type;
use Elastification\BackupRestore\Helper\DataSizeHelper;
use Elastification\BackupRestore\Helper\TimeTakenHelper;
use Elastification\BackupRestore\Helper\VersionHelper;
use Elastification\BackupRestore\Repository\ElasticsearchRepository;
use Elastification\BackupRestore\Repository\ElasticsearchRepositoryInterface;
use Elastification\BackupRestore\Repository\FilesystemRepository;
use Elastification\BackupRestore\Repository\FilesystemRepositoryInterface;
use Elastification\Client\Exception\ClientException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

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

    public function execute(BackupJob $job, OutputInterface $output)
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


        //SECTION store_data
        $this->storeData($job, $jobStats, $output);

        //global stuff
        $this->filesystem->symlinkLatestBackup($job->getPath());

        $jobStats->setTimeTaken(microtime(true) - $timeStart);
        $jobStats->setMemoryUsed(memory_get_usage() - $memoryAtStart);
        $jobStats->setMemoryUsage(memory_get_usage());
        $jobStats->setMemoryUsageReal(memory_get_usage(true));

        $output->writeln('');
        $output->writeln($this->getResultLineForOutput($jobStats));

    }

    private function storeData(BackupJob $job, JobStats $jobStats, OutputInterface $output)
    {
        $memoryAtSection = memory_get_usage();
        $timeStartSection = microtime(true);

        $docCount = $this->elastic->getDocCountByIndexType(
            $job->getHost(),
            $job->getPort(),
            $job->getMappings()->countIndices());

        $storedStats = array();

        /** @var Index $index */
        foreach($job->getMappings()->getIndices() as $index) {
            if(0 === $docCount->getDocCount($index->getName())) {
                continue;
            }

            /** @var Type $type */
            foreach($index->getTypes() as $type) {
                $docsInType = $docCount->getDocCount($index->getName(), $type->getName());

                if(0 === $docsInType) {
                    continue;
                }

                $scrollId = $this->elastic->createScrollSearch(
                    $index->getName(),
                    $type->getName(),
                    $job->getHost(),
                    $job->getPort());

                $storedStats[$index->getName()][$type->getName()]['aggregatedNumberOfDocs'] = $docsInType;
                $storedStats[$index->getName()][$type->getName()]['storedNumberOfDocs'] = 0;

                $output->writeln('<comment>Store Data for: ' . $index->getName() . '/' . $type->getName() . '</comment>');

                $progress = new ProgressBar($output, $docsInType);
                $progress->setFormat('debug');

                $progress->display();
                $progress->start();

                try {
                    while (
                        !empty($data = $this->elastic->getScrollSearchData($scrollId, $job->getHost(), $job->getPort()))
                    ) {
                        $storedDocs = $this->filesystem->storeDocuments(
                            $job->getPath(),
                            $index->getName(),
                            $type->getName(),
                            $data);

                        $storedStats[$index->getName()][$type->getName()]['storedNumberOfDocs'] += $storedDocs;
                        $progress->advance($storedDocs);
                    }
                } catch(ClientException $exception) {
                    //do nothing here
                }

                $progress->finish();
                $output->writeln(PHP_EOL);
            }
        }


        $jobStats->setStoreData(
            microtime(true) - $timeStartSection,
            memory_get_usage(),
            memory_get_usage() - $memoryAtSection,
            array('stats' => $storedStats));

        return $storedStats;
    }

    private function getResultLineForOutput(JobStats $jobStats)
    {
        $line = '<info>Finished in <comment>%s</comment>'.
            ' - MemoryUsed: <comment>%s</comment>'.
            ' - MemoryUsage: <comment>%s</comment>'.
            ' - MemoryUsageReal: <comment>%s</comment></info>';

        return sprintf($line,
            TimeTakenHelper::convert($jobStats->getTimeTaken()),
            DataSizeHelper::convert($jobStats->getMemoryUsed()),
            DataSizeHelper::convert($jobStats->getMemoryUsage()),
            DataSizeHelper::convert($jobStats->getMemoryUsageReal()));
    }


}