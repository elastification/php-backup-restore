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
        $this->indices[$index->getName()] = $index;
    }

    /**
     * Gets number of docs for index/type
     *
     * @param string $index
     * @param null|string $type
     * @return int
     * @author Daniel Wendlandt
     */
    public function getDocCount($index, $type = null)
    {
        if(!isset($this->indices[$index])) {
            return 0;
        }
        /** @var Index $indexObj */
        $indexObj = $this->indices[$index];

        if(null === $type) {
            return $indexObj->getDocsInIndex();
        }

        return $indexObj->getDocsInType($type);
    }

}