<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 15/09/15
 * Time: 12:45
 */

namespace Elastification\BackupRestore\Command;

use Elastification\BackupRestore\BusinessCase\BackupBusinessCase;
use Elastification\BackupRestore\BusinessCase\RestoreBusinessCase;
use Elastification\BackupRestore\Entity\BackupJob;
use Elastification\BackupRestore\Entity\Mappings\Index;
use Elastification\BackupRestore\Entity\Mappings\Type;
use Elastification\BackupRestore\Entity\RestoreJob;
use Elastification\BackupRestore\Entity\RestoreStrategy;
use Elastification\BackupRestore\Helper\VersionHelper;
use Elastification\BackupRestore\Repository\ElasticsearchRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class RestoreRunCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('restore:run')
            ->setDescription('Start interactive shell for restoring your data')
            ->addOption(
                'config',
                null,
                InputOption::VALUE_OPTIONAL,
                'A path to a file in yaml format'
            )
            ->addOption(
                'host',
                null,
                InputOption::VALUE_OPTIONAL,
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
                'source',
                null,
                InputOption::VALUE_OPTIONAL,
                'Defines the source folder path, where all the data is located'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //get options
        $config = $input->getOption('config');
        $host = $input->getOption('host');
        $port = $input->getOption('port');
        $source = $input->getOption('source');

        //check options and throw exception if not valid
        $this->checkOptions($config, $host);

        $restoreBusinessCase = new RestoreBusinessCase();
//
//        //config given process
//        if(null !== $config) {
//            $configJob = $backupBusinessCase->createJobFromConfig($config, $host, $port);
//
//            $output->writeln('<info>Backup Source is:</info> <comment>' .
//                $configJob->getHost() . ':' . $configJob->getPort() .
//                '</comment>' . PHP_EOL);
//            $output->writeln('<info>Indices/Types for this job are:</info>');
//
//            //display all index/type
//            /** @var Index $index */
//            foreach($configJob->getMappings()->getIndices() as $index) {
//                /** @var Type $type */
//                foreach($index->getTypes() as $type) {
//                    $output->writeln('<comment> - ' . $index->getName() . '/' . $type->getName() . '</comment>');
//                }
//            }
//            $output->writeln('');
//
//            $this->runJob($input, $output, $backupBusinessCase, $configJob);
//            return;
//        }
//
//
        //custom process
        if(null === $source) {
            $source = $this->askForSource($input, $output);
        }

        $restoreJob = $restoreBusinessCase->createJob($source, $host, $port);
        $strategy = $this->askForRestoreStrategy($input, $output, $restoreJob);
        $restoreJob->setStrategy($strategy);

        $this->runJob($input, $output, $restoreBusinessCase, $restoreJob);
    }

    /**
     * Asks for proceeding, before performing the job
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param RestoreBusinessCase $restoreBusinessCase
     * @param RestoreJob $restoreJob
     * @param bool $askForProceeding
     * @author Daniel Wendlandt
     */
    private function runJob(
        InputInterface $input,
        OutputInterface $output,
        RestoreBusinessCase $restoreBusinessCase,
        RestoreJob $restoreJob,
        $askForProceeding = true
    ) {
        if(!$askForProceeding) {
            $restoreBusinessCase->execute($restoreJob, $output);
            return;
        }

        if(!$proceed = $this->askForProceeding($input, $output)) {
            $output->writeln('<error>Aborted !!!</error>');
        } else {
            $restoreBusinessCase->execute($restoreJob, $output);
        }
    }

    /**
     * Asks for going on with the backup
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     * @author Daniel Wendlandt
     */
    private function askForProceeding(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<info>Do want to proceed with the backup?</info> [<comment>y/n</comment>]:');

        return $helper->ask($input, $output, $question);
    }

    /**
     * Asks for source path
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     * @author Daniel Wendlandt
     */
    private function askForSource(InputInterface $input, OutputInterface $output)
    {
        $samplePath = '/tmp/elastic-backup/20150923124002';
        $helper = $this->getHelper('question');
        $question = $this->getQuestion('Please enter the source path', $samplePath);

        //todo check if source exists and folder structure. If not fine, repeat this questions. Think about auto complete
        return $helper->ask($input, $output, $question);
    }

    private function askForRestoreStrategy(InputInterface $input, OutputInterface $output, RestoreJob $job)
    {
        $strategies = array(
            RestoreStrategy::STRATEGY_RESET,
            RestoreStrategy::STRATEGY_ADD,
            RestoreStrategy::STRATEGY_CUSTOM,
        );

        $questions = array(
            'Delete all indices/types if exist and insert data',
            'Do not touch indices/types if exist and add data',
            'Define custom mappings and insert the data'
        );

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion('<info>Please decide the strategy for restoring teh data ?</info>', $questions);
        $mappingStrategyAnswer = $helper->ask($input, $output, $question);

        $strategy = new RestoreStrategy();
        foreach($questions as $strategyKey =>$strategyQuestion) {
            if($mappingStrategyAnswer === $strategyQuestion) {
                $strategy->setStrategy($strategies[$strategyKey]);
            }
        }

        if(RestoreStrategy::STRATEGY_CUSTOM !== $strategy->getStrategy()) {
            $strategy->processMappingsForStrategy($strategy->getStrategy(), $job->getMappings());

            return $strategy;
        }

        //todo handle custom mappings here

        return $strategy;
    }

//    /**
//     * Asking for indeces/types wich should be used
//     *
//     * @param InputInterface $input
//     * @param OutputInterface $output
//     * @param BackupJob $job
//     * @return mixed
//     * @author Daniel Wendlandt
//     */
//    private function askForIndicesTypes(InputInterface $input, OutputInterface $output, BackupJob $job)
//    {
//        $mappings = array();
//        $mappings[] = 'all';
//
//        /** @var Index $index */
//        foreach($job->getMappings()->getIndices() as $index) {
//            /** @var Type $type */
//            foreach($index->getTypes() as $type) {
//                $mappings[] = $index->getName() . '/' . $type->getName();
//            }
//        }
//
//        $helper = $this->getHelper('question');
//        $question = new ChoiceQuestion('<info>Please select indices/types that should be dumped (separate multiple by colon)</info> [<comment>all</comment>]:', $mappings, '0');
//        $question->setMultiselect(true);
//
//        return $helper->ask($input, $output, $question);
//    }
//
    /**
     * Creates a question
     *
     * @param string $question
     * @param string $default
     * @param string $sep
     * @return Question
     * @author Daniel Wendlandt
     */
    private function getQuestion($question, $default, $sep = ':')
    {
        $questionString = $default ?
            sprintf('<info>%s</info> [<comment>%s</comment>]%s ', $question, $default, $sep) :
            sprintf('<info>%s</info>%s ', $question, $sep);

        return new Question($questionString, $default);
    }

    /**
     * Checks if options are set correctly
     *
     * @param string $host
     * @throws \Exception
     * @author Daniel Wendlandt
     */
    private function checkOptions($config, $host)
    {
        if(null === $config && null === $host) {
            throw new \Exception('Please set config or host option');
        }

//        if(null === $target) {
//            throw new \Exception('Please set target option for full backup type');
//        }
    }

}