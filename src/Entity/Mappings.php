<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 16/09/15
 * Time: 15:11
 */

namespace Elastification\BackupRestore\Entity;

use Elastification\BackupRestore\Entity\Mappings\Index;
use Elastification\BackupRestore\Entity\Mappings\Type;

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
    public function addIndex(Index $index)
    {
        $this->indices[] = $index;
    }

    /**
     * @param array $indices
     * @author Daniel Wendlandt
     */
    public function setIndices(array $indices)
    {
        $this->indices = array();

        foreach($indices as $index) {
            $this->addIndex($index);
        }
    }

    /**
     * @return int
     * @author Daniel Wendlandt
     */
    public function countIndices()
    {
        return count($this->indices);
    }

    /**
     * @return int
     * @author Daniel Wendlandt
     */
    public function countTypes()
    {
        $numberOfTypes = 0;

        /** @var Index $index */
        foreach($this->indices as $index) {
            $numberOfTypes += $index->countTypes();
        }

        return $numberOfTypes;
    }

    /**
     * Goes through all indices ans types and reduces them by given ones
     *
     * @param array $indicesTypes
     * @author Daniel Wendlandt
     */
    public function reduceIndices(array $indicesTypes)
    {
        if(empty($indicesTypes)) {
            return;
        }

        //reformat non string based array
        if(is_array($indicesTypes[0]) && isset($indicesTypes[0]['index']) && isset($indicesTypes[0]['type'])) {
            $givenIndices = $indicesTypes;
            $indicesTypes = array();

            foreach($givenIndices as $given) {
                $indicesTypes[] = $given['index'] . '/' . $given['type'];
            }
        }

        /**
         * @var string $indexName
         * @var Index $index
         */
        foreach($this->indices as $indexIdx => $index) {
            /**
             * @var string $typeName
             * @var Type $type
             */
            foreach($index->getTypes() as $typeIndex => $type) {
                $name = $index->getName() . '/' . $type->getName();

                if(!in_array($name, $indicesTypes)) {
                    $index->removeTypeByIndex($typeIndex);
                }
            }

            if(0 === $index->countTypes()) {
                unset($this->indices[$indexIdx]);
            }
        }
    }


}