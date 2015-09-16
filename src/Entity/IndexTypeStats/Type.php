<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 16/09/15
 * Time: 08:42
 */

namespace Elastification\BackupRestore\Entity\IndexTypeStats;

class Type
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $docsInType = 0;

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
    public function getDocsInType()
    {
        return $this->docsInType;
    }

    /**
     * @param int $docsInType
     */
    public function setDocsInType($docsInType)
    {
        $this->docsInType = $docsInType;
    }


}