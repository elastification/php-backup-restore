<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 16/09/15
 * Time: 08:42
 */

namespace Elastification\BackupRestore\Entity;

use Elastification\BackupRestore\Entity\IndexTypeStats\Index;

class IndexTypeStats
{
    /**
     * @var array
     */
    private $indices = array();

    /**
     * @return array
     * @author Daniel Wendlandt
     */
    public function getIndices()
    {
        return $this->indices;
    }

    /**
     * Adds a new index
     *
     * @param Index $index
     * @author Daniel Wendlandt
     */
    public function addIndex(Index $index)
    {
        $this->indices[] = $index;
    }

}