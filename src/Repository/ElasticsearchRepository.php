<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 15/09/15
 * Time: 14:21
 */
namespace Elastification\BackupRestore\Repository;

use Elastification\Client\Request\V1x\NodeInfoRequest;
use Elastification\Client\Response\V1x\NodeInfoResponse;

class ElasticsearchRepository extends AbstractElasticsearchRepository
{

    public function getServerInfo($host, $port = 9200)
    {
        $request = new NodeInfoRequest($this->getSerializer());
        $client = $this->getClient($host, $port);
        /** @var NodeInfoResponse $response */
        $response = $client->send($request);

        var_dump($response->getData()->getGatewayValue());die('lol');
    }
}

