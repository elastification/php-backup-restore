<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 17/09/15
 * Time: 07:08
 */

namespace Elastification\BackupRestore\Entity;

class BackupJob extends AbstractJob
{
    /**
     * @var string
     */
    private $target;


    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
        $this->setName($this->getCreatedAt()->format('YmdHis'));
    }

    /**
     * Gets the full path for current backup job
     *
     * @return string
     * @author Daniel Wendlandt
     */
    public function getPath()
    {
        return $this->target . DIRECTORY_SEPARATOR . $this->getName();
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

}