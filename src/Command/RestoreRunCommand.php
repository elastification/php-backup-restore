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
use Elastification\BackupRestore\Entity\Mappings;
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
    /**
     * @var Mappings
     */
    private $targetMappings;

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
        $this->targetMappings = $restoreBusinessCase->getTargetMappings($restoreJob);
        $strategy = $this->askForRestoreStrategy($input, $output, $restoreJob);
        $restoreJob->setStrategy($strategy);

        $this->askForStoringConfig($input, $output, $restoreJob);

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

    /**
     * Aksing for strategy
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param RestoreJob $job
     * @return RestoreStrategy
     * @author Daniel Wendlandt
     */
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

        $this->askForCustomMapping($input, $output, $job, $strategy);

        return $strategy;
    }

    /**
     * Asking for storing restore config
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param RestoreJob $job
     * @author Daniel Wendlandt
     */
    private function askForStoringConfig(InputInterface $input, OutputInterface $output, RestoreJob $job)
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            '<info>Do you want to store the configuration after successfull restore ?</info> [<comment>y/n</comment>]:',
            true);
        $createConfig = $helper->ask($input, $output, $question);

        if(!$createConfig) {
            $output->writeln('<error>Not storing config</error>');
            return;
        }

        $defaultName = date('YmdHis') . '_restore';
        $nameQuestion = $this->getQuestion('Please give a name for the config file', $defaultName);

        $configName = $helper->ask($input, $output, $nameQuestion);

        $job->setCreateConfig($createConfig);
        $job->setConfigName($configName);
    }

    /**
     * Asks for custom mapping
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param RestoreJob $job
     * @param RestoreStrategy $strategy
     * @author Daniel Wendlandt
     */
    private function askForCustomMapping(InputInterface $input,
                                         OutputInterface $output,
                                         RestoreJob $job,
                                         RestoreStrategy $strategy
    ) {
        $output->write(PHP_EOL);
        $questions = array();

        /** @var Index $index */
        foreach($job->getMappings()->getIndices() as $index) {
            /** @var Type $type */
            foreach($index->getTypes() as $type) {
                $mappingAction = $strategy->getMapping($index->getName(), $type->getName());
                $targetMapping = (null !== $mappingAction)
                    ? '<comment>'. $mappingAction->getStrategy() . ': ' . $mappingAction->getTargetIndex() . '/' . $mappingAction->getTargetType() . '</comment>'
                    : '<error>not set</error>';

                $questions[] = $index->getName() . '/' . $type->getName() . '[' . $targetMapping . ']';
            }
        }

        $question = new ChoiceQuestion('<info>Please map all types from backup</info>', $questions);

        $helper = $this->getHelper('question');
        $answerSource = $helper->ask($input, $output, $question);
        $indexType = explode('/', substr($answerSource, 0, strpos($answerSource, '[')));

        if(null !== $existingMappingAction = $strategy->getMapping($indexType[0], $indexType[1])) {
            $strategy->removeMappingAction($existingMappingAction);
        }

        $mappingAction = new RestoreStrategy\MappingAction();
        $mappingAction->setSourceIndex($indexType[0]);
        $mappingAction->setSourceType($indexType[1]);

        $mappingAction = $this->askForTargetMapping($input, $output, $mappingAction);
        $strategy->addMappingAction($mappingAction);

        if($job->getMappings()->countTypes() != $strategy->countMappingActions()) {
            $this->askForCustomMapping($input, $output, $job, $strategy);
        }
    }

    /**
     * Asks for target mapping. Divided into existing and custom sections
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param RestoreStrategy\MappingAction $mappingAction
     * @return RestoreStrategy\MappingAction
     * @author Daniel Wendlandt
     */
    private function askForTargetMapping(InputInterface $input,
                                         OutputInterface $output,
                                         RestoreStrategy\MappingAction $mappingAction
    ) {
        $questions = array(
            'Select existing Index/Type',
            'Enter custom Index/Type'
        );
        $question = new ChoiceQuestion('<info>Please map all types from backup</info>', $questions);
        $helper = $this->getHelper('question');
        $answerTarget = $helper->ask($input, $output, $question);

        //this is for selecting existing index/type
        if($questions[0] == $answerTarget) {
            $mappingAction = $this->askForExistingIndexType($input, $output, $mappingAction);

            return $mappingAction;
        }

        //enter custom index/type
        $mappingAction = $this->askForCustomIndexType($input, $output, $mappingAction);

        return $mappingAction;

    }

    /**
     * Ask for custom index and type names. Also sets startegy to reset
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param RestoreStrategy\MappingAction $mappingAction
     * @return RestoreStrategy\MappingAction
     * @author Daniel Wendlandt
     */
    private function askForCustomIndexType(InputInterface $input,
                                           OutputInterface $output,
                                           RestoreStrategy\MappingAction $mappingAction
    ) {
        $output->write(PHP_EOL);
        $output->writeln('<info>Please keep in mind that custom index/type will be treated with reset</info>' . PHP_EOL);

        $helper = $this->getHelper('question');

        $indexQuestion = new Question('<info>Enter the index name please: </info>');
        $index = $helper->ask($input, $output, $indexQuestion);

        $typeQuestion = new Question('<info>Enter the type name please: </info>');
        $type = $helper->ask($input, $output, $typeQuestion);

        $mappingAction->setTargetIndex($index);
        $mappingAction->setTargetType($type);
        $mappingAction->setStrategy(RestoreStrategy::STRATEGY_RESET);

        return $mappingAction;
    }

    /**
     * Forces User to select index/type from existing ones
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param RestoreStrategy\MappingAction $mappingAction
     * @return RestoreStrategy\MappingAction
     * @author Daniel Wendlandt
     */
    private function askForExistingIndexType( InputInterface $input,
                                              OutputInterface $output,
                                              RestoreStrategy\MappingAction $mappingAction
    ) {
        $existingQuestions = array();

        /** @var Index $index */
        foreach($this->targetMappings->getIndices() as $index) {
            /** @var Type $type */
            foreach($index->getTypes() as $type) {
                $existingQuestions[] = $index->getName() . '/' . $type->getName();
            }
        }

        $questionExisting = new ChoiceQuestion(
            'Please select one of this existing indices/types',
            $existingQuestions);

        $helper = $this->getHelper('question');
        $answerExisting = $helper->ask($input, $output, $questionExisting);

        $exploded = explode('/', $answerExisting);

        $mappingAction->setTargetIndex($exploded[0]);
        $mappingAction->setTargetType($exploded[1]);

        $this->askForMappingStrategy($input, $output, $mappingAction);

        return $mappingAction;
    }

    /**
     * Asks for mapping action strategy.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param RestoreStrategy\MappingAction $mappingAction
     * @return RestoreStrategy\MappingAction
     * @author Daniel Wendlandt
     */
    private function askForMappingStrategy(InputInterface $input,
                                           OutputInterface $output,
                                           RestoreStrategy\MappingAction $mappingAction)
    {
        //ask for strategy
        $strategyQuestions = array(
            'Reset the Index. [Delete Type and create mapping and insert data from Backup]',
            'Add data only. [Old type and mapping will be untouched and data will be put on top]'
        );

        $questionStrategy = new ChoiceQuestion(
            'Please select the import strategy',
            $strategyQuestions);
        $helper = $this->getHelper('question');
        $answerStrategy = $helper->ask($input, $output, $questionStrategy);

        if($strategyQuestions[0] == $answerStrategy) {
            $mappingAction->setStrategy(RestoreStrategy::STRATEGY_RESET);
        } else {
            $mappingAction->setStrategy(RestoreStrategy::STRATEGY_ADD);
        }

        return $mappingAction;
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