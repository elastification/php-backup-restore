<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 28/10/15
 * Time: 18:36
 */

namespace Elastification\BackupRestore\Tests\Fixtures\Unit\DummyClass\V1x;

use Elastification\Client\Serializer\SerializerInterface;

class DummyRequestClass
{
    /**
     * @var string
     */
    private $index;

    /**
     * @var string
     */
    private $type;

    /**
     * @var SerializerInterface
     */
    private  $serializer;

    public function __construct($index, $type, SerializerInterface $serializer)
    {
        $this->index = $index;
        $this->type = $type;
        $this->serializer = $serializer;
    }

    /**
     * @return string
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }


}
