<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 15/09/15
 * Time: 12:45
 */

namespace Elastification\BackupRestore\Command;

use Elastification\BackupRestore\BusinessCase\BackupBusinessCase;
use Elastification\BackupRestore\Entity\BackupJob;
use Elastification\BackupRestore\Entity\Mappings\Index;
use Elastification\BackupRestore\Entity\Mappings\Type;
use Elastification\BackupRestore\Helper\VersionHelper;
use Elastification\BackupRestore\Repository\ElasticsearchRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

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
                'Default port is 9200 if not set'
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
        $config = $input->getOption('config');
        $type = $input->getOption('type');
        $host = $input->getOption('host');
        $port = $input->getOption('port');
        $target = $input->getOption('target');

        //check options and throw exception if not valid
        $this->checkOptions($config, $host, $type, $target);

        $backupBusinessCase = new BackupBusinessCase();

        //config given process
        if(null !== $config) {
            $configJob = $backupBusinessCase->createJobFromConfig($config, $host, $port);

            $output->writeln('<info>Backup Source is:</info> <comment>' .
                $configJob->getHost() . ':' . $configJob->getPort() .
                '</comment>' . PHP_EOL);
            $output->writeln('<info>Indices/Types for this job are:</info>');

            //display all index/type
            /** @var Index $index */
            foreach($configJob->getMappings()->getIndices() as $index) {
                /** @var Type $type */
                foreach($index->getTypes() as $type) {
                    $output->writeln('<comment> - ' . $index->getName() . '/' . $type->getName() . '</comment>');
                }
            }
            $output->writeln('');

            $this->runJob($input, $output, $backupBusinessCase, $configJob);
            return;
        }

        if(null === $port) {
            $port = 9200;
        }

        //custom process
        if(self::OPTION_TYPE_FULL != $type && null === $target) {
            $target = $this->askForTarget($input, $output);
        }

        $backupJob = $backupBusinessCase->createJob($target, $host, $port);

        //index/type processing
        if(self::OPTION_TYPE_FULL != $type) {
            $indices = $this->askForIndicesTypes($input, $output, $backupJob);
            if($this->hasAllIndices($indices)) {
                $indices = array();
            }
            $backupJob->getMappings()->reduceIndices($indices);
        }


        $this->runJob($input, $output, $backupBusinessCase, $backupJob, self::OPTION_TYPE_FULL != $type);
    }

    /**
     * Asks for proceeding, before performing the job
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param BackupBusinessCase $backupBusinessCase
     * @param BackupJob $backupJob
     * @param bool $askForProceeding
     * @author Daniel Wendlandt
     */
    private function runJob(
        InputInterface $input,
        OutputInterface $output,
        BackupBusinessCase $backupBusinessCase,
        BackupJob $backupJob,
        $askForProceeding = true
    ) {
        if(!$askForProceeding) {
            $backupBusinessCase->execute($backupJob, $output);
            return;
        }

        if(!$proceed = $this->askForProceeding($input, $output)) {
            $output->writeln('<error>Aborted !!!</error>');
        } else {
            $backupBusinessCase->execute($backupJob, $output);
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
     * Asks for target
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     * @author Daniel Wendlandt
     */
    private function askForTarget(InputInterface $input, OutputInterface $output)
    {
        $samplePath = '/tmp/elastic-backup';
        $helper = $this->getHelper('question');
        $question = $this->getQuestion('Please enter the target path', $samplePath);

        return $helper->ask($input, $output, $question);
    }

    /**
     * Asking for indeces/types wich should be used
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param BackupJob $job
     * @return mixed
     * @author Daniel Wendlandt
     */
    private function askForIndicesTypes(InputInterface $input, OutputInterface $output, BackupJob $job)
    {
        $mappings = array();
        $mappings[] = 'all';

        /** @var Index $index */
        foreach($job->getMappings()->getIndices() as $index) {
            /** @var Type $type */
            foreach($index->getTypes() as $type) {
                $mappings[] = $index->getName() . '/' . $type->getName();
            }
        }

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion('<info>Please select indices/types that should be dumped (separate multiple by colon)</info> [<comment>all</comment>]:', $mappings, '0');
        $question->setMultiselect(true);

        return $helper->ask($input, $output, $question);
    }

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
     * @param string $type
     * @param string $target
     * @throws \Exception
     * @author Daniel Wendlandt
     */
    private function checkOptions($config, $host, $type, $target)
    {
        if(null === $config && null === $host) {
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

    /**
     * Checks if all indices is given in array
     *
     * @param array $indices
     * @return bool
     * @author Daniel Wendlandt
     */
    private function hasAllIndices(array $indices) {
        foreach($indices as $indexType) {
            if('all' === $indexType) {
                return true;
            }
        }

        return false;
    }
}