<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 15/09/15
 * Time: 13:18
 */

namespace Elastification\BackupRestore\Repository;

use Elastification\Client\Client;
use Elastification\Client\ClientInterface;
use Elastification\Client\Request\RequestManager;
use Elastification\Client\Serializer\NativeJsonSerializer;
use Elastification\Client\Serializer\SerializerInterface;
use Elastification\Client\Transport\HttpGuzzle\GuzzleTransport;
use GuzzleHttp\Client as GuzzleClient;

abstract class AbstractElasticsearchRepository
{
    const CLIENT_SEPARATOR = ':';

    /**
     * @var array
     */
    private $clients = [];

    /**
     * @var SerializerInterface
     */
    private $serializer = null;

    /**
     * Gets a client with checking an instance cache.
     * If not exists, then a new client will be created
     *
     * @param string $host
     * @param int $port
     * @return ClientInterface
     * @author Daniel Wendlandt
     */
    protected function getClient($host, $port = 9200)
    {
        $clientName = $this->createClientName($host, $port);

        if(!isset($this->clients[$clientName])) {
            $this->clients[$clientName] = $this->createClient($host, $port);
        }

        return $this->clients[$clientName];
    }

    /**
     * Gets an instance of the serializer. With instance check
     *
     * @return SerializerInterface|null
     * @author Daniel Wendlandt
     */
    protected function getSerializer()
    {
        if(null === $this->serializer){
            $this->serializer = new NativeJsonSerializer();
        }

        return $this->serializer;
    }

    /**
     * Creates a new client for given host and port
     *
     * @param string $host
     * @param int $port
     * @return Client
     * @author Daniel Wendlandt
     */
    private function createClient($host, $port = 9200)
    {
        $guzzleClient = new GuzzleClient(array('base_uri' => $this->createClientUri($host, $port)));

        return new Client(new GuzzleTransport($guzzleClient), new RequestManager());
    }

    /**
     * Creates a client name for given params
     *
     * @param string $host
     * @param int $port
     * @return string
     * @author Daniel Wendlandt
     */
    private function createClientName($host, $port = 9200)
    {
        return $host . self::CLIENT_SEPARATOR . $port;
    }

    /**
     * Creates the client uri
     *
     * @param string $host
     * @param int $port
     * @return string
     * @author Daniel Wendlandt
     */
    private function createClientUri($host, $port = 9200)
    {
        return 'http://' . $host . ':' . $port . '/';
    }
}
