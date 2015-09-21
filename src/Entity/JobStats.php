<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 17/09/15
 * Time: 09:21
 */

namespace Elastification\BackupRestore\Entity;

use Elastification\BackupRestore\Entity\JobStats\SectionData;

class JobStats
{
    const NAME_CREATE_STRUCTURE = 'create_structure';
    const NAME_STORE_MAPPINGS = 'store_mappings';
    const NAME_STORE_DATA = 'store_data';
    const NAME_STORE_META_DATA = 'store_meta_data';

    /**
     * @var array
     */
    private $data = array();

    /**
     * @var float
     */
    private $timeTaken = 0;

    /**
     * @var int
     */
    private $memoryUsage = 0;

    /**
     * @var int
     */
    private $memoryUsageReal = 0;

    /**
     * @var int
     */
    private $memoryUsed = 0;

    /**
     * @var \DateTime
     */
    private $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * Sets the data for create_structure section
     *
     * @param double $timeTaken
     * @param int $memoryUsage
     * @param int $memoryUsed
     * @param array $options
     * @author Daniel Wendlandt
     */
    public function setCreateStructure($timeTaken, $memoryUsage, $memoryUsed, array $options = array())
    {
        $this->setData(self::NAME_CREATE_STRUCTURE, $timeTaken, $memoryUsage, $memoryUsed, $options);
    }

    /**
     * Sets the data for store_mappings section
     *
     * @param double $timeTaken
     * @param int $memoryUsage
     * @param int $memoryUsed
     * @param array $options
     * @author Daniel Wendlandt
     */
    public function setStoreMappings($timeTaken, $memoryUsage, $memoryUsed, array $options = array())
    {
        $this->setData(self::NAME_STORE_MAPPINGS, $timeTaken, $memoryUsage, $memoryUsed, $options);
    }

    /**
     * Sets the data for store_data section
     *
     * @param double $timeTaken
     * @param int $memoryUsage
     * @param int $memoryUsed
     * @param array $options
     * @author Daniel Wendlandt
     */
    public function setStoreData($timeTaken, $memoryUsage, $memoryUsed, array $options = array())
    {
        $this->setData(self::NAME_STORE_DATA, $timeTaken, $memoryUsage, $memoryUsed, $options);
    }

    /**
     * Sets the data for store_meta_data section
     *
     * @param double $timeTaken
     * @param int $memoryUsage
     * @param int $memoryUsed
     * @param array $options
     * @author Daniel Wendlandt
     */
    public function setStoreMetaData($timeTaken, $memoryUsage, $memoryUsed, array $options = array())
    {
        $this->setData(self::NAME_STORE_META_DATA, $timeTaken, $memoryUsage, $memoryUsed, $options);
    }

    /**
     * @return int
     */
    public function getTimeTaken()
    {
        return $this->timeTaken;
    }

    /**
     * @param int $timeTaken
     */
    public function setTimeTaken($timeTaken)
    {
        $this->timeTaken = $timeTaken;
    }

    /**
     * @return int
     */
    public function getMemoryUsage()
    {
        return $this->memoryUsage;
    }

    /**
     * @param int $memoryUsage
     */
    public function setMemoryUsage($memoryUsage)
    {
        $this->memoryUsage = $memoryUsage;
    }

    /**
     * @return int
     */
    public function getMemoryUsed()
    {
        return $this->memoryUsed;
    }

    /**
     * @param int $memoryUsed
     */
    public function setMemoryUsed($memoryUsed)
    {
        $this->memoryUsed = $memoryUsed;
    }

    /**
     * @return int
     */
    public function getMemoryUsageReal()
    {
        return $this->memoryUsageReal;
    }

    /**
     * @param int $memoryUsageReal
     */
    public function setMemoryUsageReal($memoryUsageReal)
    {
        $this->memoryUsageReal = $memoryUsageReal;
    }

    /**
     * @return array
     * @author Daniel Wendlandt
     */
    public function toArray()
    {
        $data = array();

        /** @var SectionData $sectionData */
        foreach($this->data as $sectionName => $sectionData) {
            $data[$sectionName] = $sectionData->toArray();
        }

        return array(
            'timeTaken' => $this->timeTaken,
            'memoryUsage' => $this->memoryUsage,
            'memoryUsageReal' => $this->memoryUsageReal,
            'memoryUsed' => $this->memoryUsed,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'data' => $data,
        );
    }

    /**
     * Sets data by name in data array
     *
     * @param string $name
     * @param double $timeTaken
     * @param int$memoryUsage
     * @param int $memoryUsed
     * @param array $options
     * @author Daniel Wendlandt
     */
    private function setData($name, $timeTaken, $memoryUsage, $memoryUsed, array $options)
    {
        $dataObj = new SectionData();
        $dataObj->setName($name);
        $dataObj->setTimeTaken($timeTaken);
        $dataObj->setMemoryUsage($memoryUsage);
        $dataObj->setMemoryUsed($memoryUsed);
        $dataObj->setOptions($options);

        $this->data[$name] = $dataObj;
    }
}