<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 15/09/15
 * Time: 12:45
 */

namespace Elastification\BackupRestore\Command;

use Elastification\BackupRestore\Repository\ElasticsearchRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BackupRunCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('backup:run')
            ->setDescription('Start interactive shell for creating a backup of your data')
//            ->addArgument(
//                'name',
//                InputArgument::OPTIONAL,
//                'Who do you want to greet?'
//            )
//            ->addOption(
//                'yell',
//                null,
//                InputOption::VALUE_NONE,
//                'If set, the task will yell in uppercase letters'
//            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $elastic = new ElasticsearchRepository();
        var_dump($elastic->getServerInfo('localhost'));


        $output->writeln('jeppa backup');
    }
}