<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 16/09/15
 * Time: 15:11
 */

namespace Elastification\BackupRestore\Entity;

use Elastification\BackupRestore\Entity\Mappings\Index;

class Mappings
{
    private $indices = array();

    /**
     * @return array
     */
    public function getIndices()
    {
        return $this->indices;
    }

    /**
     * @param Index $index
     */
    public function addIndices(Index $index)
    {
        $this->indices[] = $index;
    }


}