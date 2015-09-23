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

}