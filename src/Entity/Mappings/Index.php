<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 16/09/15
 * Time: 08:42
 */

namespace Elastification\BackupRestore\Entity\Mappings;

class Index
{
    /**
     * @var string
     */
    private $name;

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
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @return int
     * @author Daniel Wendlandt
     */
    public function countTypes()
    {
        return count($this->types);
    }

    /**
     * @param Type $type
     */
    public function addType(Type $type)
    {
        $this->types[] = $type;
    }

    /**
     * removes a type by index
     *
     * @param int $index
     * @author Daniel Wendlandt
     */
    public function removeTypeByIndex($index)
    {
        if(isset($this->types[$index])) {
            unset($this->types[$index]);
        }
    }


}