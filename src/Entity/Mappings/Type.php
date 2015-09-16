<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 16/09/15
 * Time: 08:42
 */

namespace Elastification\BackupRestore\Entity\Mappings;

class Type
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $schema = array();

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
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @param array $schema
     */
    public function setSchema(array $schema)
    {
        $this->schema = $schema;
    }


}