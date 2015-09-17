<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 17/09/15
 * Time: 07:08
 */

namespace Elastification\BackupRestore\Entity;

class BackupJob
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $target;

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

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->name = $this->createdAt->format('YmdHis');
    }

    /**
     * Gets the full path for current backup job
     *
     * @return string
     * @author Daniel Wendlandt
     */
    public function getPath()
    {
        return $this->target . DIRECTORY_SEPARATOR . $this->name;
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
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param string $target
     */
    public function setTarget($target)
    {
        $this->target = $target;
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