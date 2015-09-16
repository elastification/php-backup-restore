<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 15/09/15
 * Time: 12:45
 */

namespace Elastification\BackupRestore\Command;

use Elastification\BackupRestore\Helper\VersionHelper;
use Elastification\BackupRestore\Repository\ElasticsearchRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BackupRunCommand extends Command
{
    const OPTION_TYPE_FULL = 'full';

    /**
     * @var array
     */
    private static $optionTypes = array('custom', 'full');

    protected function configure()
    {
        $this
            ->setName('backup:run')
            ->setDescription('Start interactive shell for creating a backup of your data')
            ->addOption(
                'host',
                null,
                InputOption::VALUE_REQUIRED,
                'If config is not set, this is required.'
            )
            ->addOption(
                'port',
                null,
                InputOption::VALUE_OPTIONAL,
                'Default port is 9200 if not set',
                9200
            )
            ->addOption(
                'type',
                null,
                InputOption::VALUE_OPTIONAL,
                'Choose between "full" and "custom" backup. Custom backup will start an interactive process',
                'custom'
            )
            ->addOption(
                'target',
                null,
                InputOption::VALUE_OPTIONAL,
                'Defines a target directory where you data will be stored. (example: /tmp/my-backups) This is required for type=full'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //get options
        $type = $input->getOption('type');
        $host = $input->getOption('host');
        $port = $input->getOption('port');
        $target = $input->getOption('target');
        //check options and throw exception if not valid
        $this->checkOptions($host, $type, $target);

        //get server info
        $elastic = new ElasticsearchRepository();
        $serverInfo = $elastic->getServerInfo($host, $port);

        if(!VersionHelper::isVersionAllowed($serverInfo->version)) {
            throw new \Exception('Elasticsearch version ' . $serverInfo->version . ' is not supported by this tool');
        }

        //var_dump($serverInfo);
//        var_dump($elastic->getDocCountByIndexType($host, $port));
        var_dump($elastic->getAllMappings($host, $port));


        //$output->writeln('jeppa backup');
    }

    /**
     * Checks if options are set correctly
     *
     * @param string $host
     * @param string $type
     * @param string $target
     * @throws \Exception
     * @author Daniel Wendlandt
     */
    private function checkOptions($host, $type, $target)
    {
        if(null === $host) {
            throw new \Exception('Please set config or host option');
        }

        if(!in_array($type, static::$optionTypes)) {
            throw new \Exception('Type is is not valid. Make shore, that you are using one of this [' .
                implode(',', static::$optionTypes)
                . ']');
        }

        if(self::OPTION_TYPE_FULL == $type && null === $target) {
            throw new \Exception('Please set target option for full backup type');
        }
    }
}