<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 16/09/15
 * Time: 18:18
 */

namespace Elastification\BackupRestore\BusinessCase;

use Elastification\BackupRestore\Entity\JobStats;
use Elastification\BackupRestore\Entity\Mappings\Index;
use Elastification\BackupRestore\Entity\Mappings\Type;
use Elastification\BackupRestore\Entity\RestoreJob;
use Elastification\BackupRestore\Entity\RestoreStrategy;
use Elastification\BackupRestore\Helper\DataSizeHelper;
use Elastification\BackupRestore\Helper\TimeTakenHelper;
use Elastification\BackupRestore\Helper\VersionHelper;
use Elastification\BackupRestore\Repository\ElasticsearchRepository;
use Elastification\BackupRestore\Repository\ElasticsearchRepositoryInterface;
use Elastification\BackupRestore\Repository\FilesystemRepository;
use Elastification\BackupRestore\Repository\FilesystemRepositoryInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class RestoreBusinessCase
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
        } else {
            $this->elastic = $elastic;
        }

        if(null === $filesystem) {
            $this->filesystem = new FilesystemRepository();
        } else {
            $this->filesystem = $filesystem;
        }
    }

    /**
     * Creates a backup job
     *
     * @param string $source
     * @param string $host
     * @param int $port
     * @return RestoreJob
     * @throws \Exception
     * @author Daniel Wendlandt
     */
    public function createJob($source, $host, $port = 9200)
    {
        $restoreJob = new RestoreJob();
        $restoreJob->setHost($host);
        $restoreJob->setPort($port);
        $restoreJob->setSource(dirname($source));
        $restoreJob->setName(basename($source));

        $restoreJob->setServerInfo($this->elastic->getServerInfo($host, $port));
        $restoreJob->setMappings($this->filesystem->loadMappings($restoreJob->getPath()));

        if(!VersionHelper::isVersionAllowed($restoreJob->getServerInfo()->version)) {
            throw new \Exception('Elasticsearch version ' .
                $restoreJob->getServerInfo()->version .
                ' is not supported by this tool');
        }

        return $restoreJob;
    }

    /**
     * Creates a job from given config file in yaml format
     *
     * @param string $filepath
     * @param null|string $host
     * @param null|string $port
     * @return RestoreJob
     * @throws \Exception
     * @author Daniel Wendlandt
     */
    public function createJobFromConfig($filepath, $host = null, $port = null)
    {
        $config = $this->filesystem->loadYamlConfig($filepath);

        if(null === $host) {
            $host = $config['host'];
        }

        if(null === $port) {
            $port = $config['port'];
        }

        $source = $config['source'] . DIRECTORY_SEPARATOR . $config['name'];

        $job = $this->createJob($source, $host, $port);
        $strategy = new RestoreStrategy();
        $strategy->setStrategy($config['strategy']['strategy']);

        foreach($config['strategy']['mappings'] as $actionConfig) {
            $mappingAction = RestoreStrategy\MappingAction::createFromArray($actionConfig);
            $strategy->addMappingAction($mappingAction);
        }

        $job->setStrategy($strategy);

        return $job;
    }

    /**
     * Loads possible target mappings
     *
     * @param RestoreJob $job
     * @return \Elastification\BackupRestore\Entity\Mappings
     * @author Daniel Wendlandt
     */
    public function getTargetMappings(RestoreJob $job)
    {
        return $this->elastic->getAllMappings($job->getHost(), $job->getPort());
    }

    /**
     * Runs the specified job and returns job statistics
     *
     * @param RestoreJob $job
     * @param OutputInterface $output
     * @author Daniel Wendlandt
     * @return JobStats
     */
    public function execute(RestoreJob $job, OutputInterface $output)
    {
        $memoryAtStart = memory_get_usage();
        $timeStart = microtime(true);

        $jobStats = new JobStats();

        //SECTION handle_indices_and_mappings
        $this->handleMappings($job, $jobStats, $output);

        //SECTION restore_data
        $storedStats = $this->restoreData($job, $jobStats, $output);


        //Section store_meta_data
        $this->storeMetaData($job, $jobStats, $storedStats, $output);

        //store backup as config in config folder
        if($job->isCreateConfig()) {
            $this->storeConfig($job, $output);
        }

        //handle jobs stats and tore it to filesystem
        $jobStats->setTimeTaken(microtime(true) - $timeStart);
        $jobStats->setMemoryUsed(memory_get_usage() - $memoryAtStart);
        $jobStats->setMemoryUsage(memory_get_usage());
        $jobStats->setMemoryUsageReal(memory_get_usage(true));

        $this->storeJobStats($job, $jobStats, $output);

        $output->writeln('');
        $output->writeln($this->getResultLineForOutput($jobStats));

        return $jobStats;
    }

    /**
     * Take care of all mapping actions and handles all for elastic sid
     *
     * @param RestoreJob $job
     * @param JobStats $jobStats
     * @param OutputInterface $output
     * @throws \Exception
     * @author Daniel Wendlandt
     */
    private function handleMappings(RestoreJob $job, JobStats $jobStats, OutputInterface $output)
    {
        $memoryAtSection = memory_get_usage();
        $timeStartSection = microtime(true);

        $output->writeln('<info>*** Start handling mappings ***</info>' . PHP_EOL);
        /** @var Index $index */
        foreach($job->getMappings()->getIndices() as $index) {
            /** @var Type $type */
            foreach($index->getTypes() as $type) {
                $mappingAction = $job->getStrategy()->getMapping($index->getName(), $type->getName());

                if(null === $mappingAction) {
                    throw new \Exception(
                        'Mapping action missing for "' . $index->getName() . '/' . $type->getName() . '"');
                }

                if(RestoreStrategy::STRATEGY_RESET === $mappingAction->getStrategy()) {

                    $schema = array($mappingAction->getTargetType() => $type->getSchema());
                    $this->elastic->createMapping(
                        $mappingAction->getTargetIndex(),
                        $mappingAction->getTargetType(),
                        $schema, $job->getHost(),
                        $job->getPort());

                    $output->writeln('<comment> - Created mapping from ' .
                        $index->getName() . '/' . $type->getName() .
                        ' to ' .
                        $mappingAction->getTargetIndex() . '/' . $mappingAction->getTargetType() .
                        '</comment>');
                } else {
                    $output->writeln('<comment> - No Action for ' .
                        $index->getName() . '/' . $type->getName() .
                        '</comment>');
                }
            }
        }

        $output->writeln('');

        $jobStats->setRestoreHandleMappings(
            microtime(true) - $timeStartSection,
            memory_get_usage(),
            memory_get_usage() - $memoryAtSection);
    }

    private function restoreData(RestoreJob $job, JobStats $jobStats, OutputInterface $output)
    {
        $memoryAtSection = memory_get_usage();
        $timeStartSection = microtime(true);

        $output->writeln('<info>*** Starting with data restoring ***</info>' . PHP_EOL);

        $storedStats = array();

        /** @var Index $index */
        foreach($job->getMappings()->getIndices() as $index) {
            $toIndex = null;

            /** @var Type $type */
            foreach($index->getTypes() as $type) {
                /** @var Finder $finder */
                $finder = $this->filesystem->loadDataFiles($job->getPath(), $index->getName(), $type->getName());

                if(null === $finder) {
                    continue;
                }

                if(!isset($storedStats[$index->getName()])) {
                    $storedStats[$index->getName()] = array();
                }

                if(!isset($storedStats[$index->getName()][$type->getName()])) {
                    $storedStats[$index->getName()][$type->getName()] = array(
                        'filesRead' => 0,
                        'dataInType' => 0
                    );
                }

                $mappingAction = $job->getStrategy()->getMapping($index->getName(), $type->getName());

                if(null === $mappingAction) {
                    throw new \Exception(
                        'Mapping action missing for "' . $index->getName() . '/' . $type->getName() . '"');
                }

                $indexSettingsResponse = $this->elastic->getIndexSettings($mappingAction->getTargetIndex(), $job->getHost(), $job->getPort());
                $indexSettings = $indexSettingsResponse->getData()[$mappingAction->getTargetIndex()];
                $indexSettings = isset($indexSettings['settings']) ? $indexSettings['settings'] : [];
                $refreshInterval = isset($indexSettings['refresh_interval']) ? $indexSettings['refresh_interval'] : '1s';

                $this->elastic->updateIndexSettings(
                    $mappingAction->getTargetIndex(),
                    [ 'index.refresh_interval' => -1 ],
                    $job->getHost(),
                    $job->getPort()
                );

                $docCount = $finder->count();

                $output->writeln('<comment>Store Data for:</comment>');
                $output->writeln('<comment> [from]' . $index->getName() . '/' . $type->getName() . '</comment>');
                $output->writeln('<comment> [to]' . $mappingAction->getTargetIndex() .
                    '/' . $mappingAction->getTargetType() . '</comment>' . PHP_EOL);

                $progress = new ProgressBar($output, $docCount);
                $progress->setFormat('debug');
                $progress->display();
                $progress->start();

                /** @var SplFileInfo $file */
                foreach($finder as $file) {

                    $data = json_decode($file->getContents(), true);
                    $id = $data['_id'];

                    $this->elastic->createDocument(
                        $mappingAction->getTargetIndex(),
                        $mappingAction->getTargetType(),
                        $id,
                        $data['_source'],
                        $job->getHost(),
                        $job->getPort());

                    $progress->advance();
                    $storedStats[$index->getName()][$type->getName()]['filesRead']++;
                }

                $progress->finish();
                $output->writeln(PHP_EOL);
                $toIndex = $mappingAction->getTargetIndex();

                $this->elastic->updateIndexSettings(
                    $mappingAction->getTargetIndex(),
                    [ 'index.refresh_interval' => $refreshInterval ],
                    $job->getHost(),
                    $job->getPort()
                );
            }

            $this->elastic->refreshIndex($toIndex, $job->getHost(), $job->getPort());
        }

        //add aggregated to storedStats for comparing later
        $docStats = $this->elastic->getDocCountByIndexType($job->getHost(), $job->getPort());
        foreach($storedStats as $indexName => $types) {
            foreach($types as $typeName => $typeStats) {
                $storedStats[$indexName][$typeName]['dataInType'] = $docStats->getDocCount($indexName, $typeName);
            }
        }

        $jobStats->setRestoreData(microtime(true) - $timeStartSection,
            memory_get_usage(),
            memory_get_usage() - $memoryAtSection,
            array('storedStats' => $storedStats));

        return $storedStats;
    }

    public function storeMetaData(RestoreJob $job, JobStats $jobStats, array $storedStats, OutputInterface $output)
    {
        $memoryAtSection = memory_get_usage();
        $timeStartSection = microtime(true);

        $output->writeln('<info>*** Starting with meta data storing ***</info>' . PHP_EOL);

        $this->filesystem->storeRestoreServerInfo($job->getPath(), $job->getCreatedAt(), $job->getServerInfo());
        $output->writeln('<comment> - Stored server-info file</comment>' . PHP_EOL);

        $this->filesystem->storeRestoreStoredStats($job->getPath(), $job->getCreatedAt(), $storedStats);
        $output->writeln('<comment> - Stored stored-stats file</comment>' . PHP_EOL);

        $output->writeln('');

        $jobStats->setStoreMetaData(microtime(true) - $timeStartSection,
            memory_get_usage(),
            memory_get_usage() - $memoryAtSection,
            array('storedStats' => $storedStats));
    }
    /**
     * Stores jobs stats into json format in meta files
     *
     * @param RestoreJob $job
     * @param JobStats $jobStats
     * @param OutputInterface $output
     * @author Daniel Wendlandt
     */
    private function storeJobStats(RestoreJob $job, JobStats $jobStats, OutputInterface $output)
    {
        $this->filesystem->storeRestoreJobStats($job->getPath(), $jobStats);
        $output->writeln('<info>*** Stored job-stats to file ***</info>' . PHP_EOL);
    }

    /**
     * Stores config to yml config
     *
     * @param RestoreJob $job
     * @param OutputInterface $output
     * @author Daniel Wendlandt
     */
    private function storeConfig(RestoreJob $job, OutputInterface $output)
    {
        $mappingActions = array();

        foreach($job->getStrategy()->getMappings() as $types) {
            /** @var RestoreStrategy\MappingAction $mappingAction */
            foreach($types as $mappingAction) {
                $mappingActions[] = $mappingAction->toArray();
            }
        }

        $config = array(
            'name' => $job->getName(),
            'source' => $job->getSource(),
            'host' => $job->getHost(),
            'port' => $job->getPort(),
            'create_config' => $job->isCreateConfig(),
            'config_name' => $job->getConfigName(),
            'strategy' => array(
                'strategy' => $job->getStrategy()->getStrategy(),
                'mappings' => $mappingActions
            ),
            'created_at' => $job->getCreatedAt()->format('Y-m-d H:i:s')

        );

        $this->filesystem->storeRestoreConfig($job, $config);

        $output->writeln('<info>*** Stored restore-config to file in yaml format ***</info>' . PHP_EOL);
    }

    /**
     * Gets the content for the result line
     *
     * @param JobStats $jobStats
     * @return string
     * @author Daniel Wendlandt
     */
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