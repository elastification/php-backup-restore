<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 16/09/15
 * Time: 08:42
 */

namespace Elastification\BackupRestore\Entity\IndexTypeStats;

class Index
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $docsInIndex = 0;

    /**
     * @var array
     */
    private $types = array();

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getDocsInIndex()
    {
        return $this->docsInIndex;
    }

    /**
     * @param int $docsInIndex
     */
    public function setDocsInIndex($docsInIndex)
    {
        $this->docsInIndex = $docsInIndex;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param Type $type
     */
    public function addType(Type $type)
    {
        $this->types[$type->getName()] = $type;
    }

    /**
     * @param string $type
     * @return int
     * @author Daniel Wendlandt
     */
    public function getDocsInType($type)
    {
        if(!isset($this->types[$type])) {
            return 0;
        }
        /** @var Type $typeObj */
        $typeObj = $this->types[$type];

        return $typeObj->getDocsInType();
    }


}