<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 17/09/15
 * Time: 07:08
 */

namespace Elastification\BackupRestore\Entity;

abstract class AbstractJob
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port = 9200;

    /**
     * @var Mappings
     */
    private $mappings;

    /**
     * @var ServerInfo
     */
    private $serverInfo;

    /**
     * @var \DateTime
     */
    private $createdAt;


    /**
     * Gets the full path for current backup job
     *
     * @return string
     * @author Daniel Wendlandt
     */
    abstract public function getPath();


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
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = (int)$port;
    }

    /**
     * @return Mappings
     */
    public function getMappings()
    {
        return $this->mappings;
    }

    /**
     * @param Mappings $mappings
     */
    public function setMappings(Mappings $mappings)
    {
        $this->mappings = $mappings;
    }

    /**
     * @return ServerInfo
     */
    public function getServerInfo()
    {
        return $this->serverInfo;
    }

    /**
     * @param ServerInfo $serverInfo
     */
    public function setServerInfo(ServerInfo $serverInfo)
    {
        $this->serverInfo = $serverInfo;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

}