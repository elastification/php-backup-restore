<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 17/09/15
 * Time: 09:49
 */

namespace Elastification\BackupRestore\Entity\JobStats;

class SectionData
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var double
     */
    private $timeTaken = 0;

    /**
     * @var int
     */
    private $memoryUsage = 0;

    /**
     * @var int
     */
    private $memoryUsed = 0;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var array
     */
    private $options = array();

    
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

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
     * @return double
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
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return array
     * @author Daniel Wendlandt
     */
    public function toArray()
    {
        return array(
            'name' => $this->name,
            'timeTaken' => $this->timeTaken,
            'memoryUsage' => $this->memoryUsage,
            'memoryUsed' => $this->memoryUsed,
            'options' => $this->options,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s')
        );
    }

}