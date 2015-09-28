<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 23/09/15
 * Time: 14:26
 */

namespace Elastification\BackupRestore\Entity;

use Elastification\BackupRestore\Entity\Mappings\Index;
use Elastification\BackupRestore\Entity\Mappings\Type;
use Elastification\BackupRestore\Entity\RestoreStrategy\MappingAction;

class RestoreStrategy
{
    const STRATEGY_RESET = 'reset';
    const STRATEGY_ADD = 'add';
    const STRATEGY_CUSTOM = 'custom';

    /**
     * @var string
     */
    private $strategy;

    /**
     * @var array
     */
    private $mappings = array();


    /**
     * @param $strategy
     * @throws \Exception
     * @author Daniel Wendlandt
     */
    public static function isStrategyAllowed($strategy)
    {
        if(!in_array($strategy, static::getAllowedStrategies())) {
            throw new \Exception('Strategy "' . $strategy . '" is not allowed');
        }
    }

    /**
     * @return array
     * @author Daniel Wendlandt
     */
    private static function getAllowedStrategies()
    {
        return array(
            self::STRATEGY_RESET,
            self::STRATEGY_ADD,
            self::STRATEGY_CUSTOM
        );
    }

    /**
     * @return string
     */
    public function getStrategy()
    {
        return $this->strategy;
    }


    /**
     * @param string $strategy
     * @throws \Exception
     */
    public function setStrategy($strategy)
    {
        $this->isStrategyAllowed($strategy);

        $this->strategy = $strategy;
    }

    /**
     * @return array
     * @author Daniel Wendlandt
     */
    public function getMappings()
    {
        return $this->mappings;
    }

    /**
     * @param string $index
     * @param string $type
     * @return null|MappingAction
     * @author Daniel Wendlandt
     */
    public function getMapping($index, $type)
    {
        if(!isset($this->mappings[$index])) {
            return null;
        }

        if(!isset($this->mappings[$index][$type])) {
            return null;
        }


        return $this->mappings[$index][$type];
    }

    /**
     * @param MappingAction $mappingAction
     * @author Daniel Wendlandt
     */
    public function addMappingAction(MappingAction $mappingAction)
    {
        if(!isset($this->mappings[$mappingAction->getSourceIndex()])) {
            $this->mappings[$mappingAction->getSourceIndex()] = array();
        }

        $this->mappings[$mappingAction->getSourceIndex()][$mappingAction->getSourceType()] = $mappingAction;
    }

    /**
     * Process all given indices/types for only one main strategy
     *
     * @param string $strategy
     * @param Mappings $mappings
     * @author Daniel Wendlandt
     */
    public function processMappingsForStrategy($strategy, Mappings $mappings)
    {
        $this->mappings = array();

        /** @var Index $index */
        foreach($mappings->getIndices() as $index) {
            /** @var Type $type */
            foreach($index->getTypes() as $type) {
                $mappingAction = new MappingAction();
                $mappingAction->setStrategy($strategy);
                $mappingAction->setSourceIndex($index->getName());
                $mappingAction->setSourceType($type->getName());
                $mappingAction->setTargetIndex($index->getName());
                $mappingAction->setTargetType($type->getName());

                $this->addMappingAction($mappingAction);
            }
        }
    }

    /**
     * Returns the number of mapping actions
     *
     * @return int
     * @author Daniel Wendlandt
     */
    public function countMappingActions()
    {
        $numberOfActions = 0;

        foreach($this->mappings as $types) {
            $numberOfActions += count($types);
        }

        return $numberOfActions;
    }

    /**
     * Removes a mapping action
     *
     * @param MappingAction $mappingAction
     * @author Daniel Wendlandt
     */
    public function removeMappingAction(MappingAction $mappingAction)
    {
        foreach($this->mappings as $indexName => $types) {
            foreach($types as $typeName => $type) {
                if($mappingAction === $type) {
                    unset($this->mappings[$indexName][$typeName]);
                }
            }
        }
    }


}