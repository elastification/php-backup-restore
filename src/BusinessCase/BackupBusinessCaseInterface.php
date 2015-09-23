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
use Symfony\Component\Yaml\Dumper;

interface BackupBusinessCaseInterface
{
    /**
     * Creates a backup job
     *
     * @param string $target
     * @param string $host
     * @param int $port
     * @param array $mappings
     * @return BackupJob
     * @throws \Exception
     * @author Daniel Wendlandt
     */
    public function createJob($target, $host, $port = 9200, array $mappings = array());

    /**
     * Creates a job from given config file in yaml format
     *
     * @param string $filepath
     * @param null|string $host
     * @param null|string $port
     * @return BackupJob
     * @throws \Exception
     * @author Daniel Wendlandt
     */
    public function createJobFromConfig($filepath, $host = null, $port = null);

    /**
     * Runs the specified job and returns job statistics
     *
     * @param BackupJob $job
     * @param OutputInterface $output
     * @author Daniel Wendlandt
     * @return JobStats
     */
    public function execute(BackupJob $job, OutputInterface $output);

}