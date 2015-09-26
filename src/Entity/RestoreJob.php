<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 17/09/15
 * Time: 07:08
 */

namespace Elastification\BackupRestore\Entity;

class RestoreJob extends AbstractJob
{

    /**
     * @var string
     */
    private $source;

    /**
     * @var RestoreStrategy
     */
    private $strategy;

    /**
     * @var bool
     */
    private $createConfig = true;

    /**
     * @var string
     */
    private $configName;

    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
    }

    /**
     * Gets the full path for current backup job
     *
     * @return string
     * @author Daniel Wendlandt
     */
    public function getPath()
    {
        return $this->source . DIRECTORY_SEPARATOR . $this->getName();
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return RestoreStrategy
     */
    public function getStrategy()
    {
        return $this->strategy;
    }

    /**
     * @param RestoreStrategy $strategy
     */
    public function setStrategy(RestoreStrategy $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * @return boolean
     */
    public function isCreateConfig()
    {
        return $this->createConfig;
    }

    /**
     * @param boolean $createConfig
     */
    public function setCreateConfig($createConfig)
    {
        $this->createConfig = (bool) $createConfig;
    }

    /**
     * @return string
     */
    public function getConfigName()
    {
        return $this->configName;
    }

    /**
     * @param string $configName
     */
    public function setConfigName($configName)
    {
        $this->configName = (string) $configName;
    }



}