<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 23/09/15
 * Time: 15:38
 */

namespace Elastification\BackupRestore\Entity\RestoreStrategy;

use Elastification\BackupRestore\Entity\RestoreStrategy;

class MappingAction
{
    /**
     * @var string
     */
    private $strategy;

    /**
     * @var string
     */
    private $sourceIndex;

    /**
     * @var string
     */
    private $sourceType;

    /**
     * @var string
     */
    private $targetIndex;

    /**
     * @var string
     */
    private $targetType;

    /**
     * @return string
     * @author Daniel Wendlandt
     */
    public function getStrategy()
    {
        return $this->strategy;
    }

    /**
     * @param string $strategy
     */
    public function setStrategy($strategy)
    {
        RestoreStrategy::isStrategyAllowed($strategy);

        $this->strategy = $strategy;
    }

    /**
     * @return string
     */
    public function getSourceIndex()
    {
        return $this->sourceIndex;
    }

    /**
     * @param string $sourceIndex
     */
    public function setSourceIndex($sourceIndex)
    {
        $this->sourceIndex = $sourceIndex;
    }

    /**
     * @return string
     */
    public function getSourceType()
    {
        return $this->sourceType;
    }

    /**
     * @param string $sourceType
     */
    public function setSourceType($sourceType)
    {
        $this->sourceType = $sourceType;
    }

    /**
     * @return string
     */
    public function getTargetIndex()
    {
        return $this->targetIndex;
    }

    /**
     * @param string $targetIndex
     */
    public function setTargetIndex($targetIndex)
    {
        $this->targetIndex = $targetIndex;
    }

    /**
     * @return string
     */
    public function getTargetType()
    {
        return $this->targetType;
    }

    /**
     * @param string $targetType
     */
    public function setTargetType($targetType)
    {
        $this->targetType = $targetType;
    }



}

