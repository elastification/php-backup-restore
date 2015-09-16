<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 16/09/15
 * Time: 10:42
 */

namespace Elastification\BackupRestore\Repository\ElasticQuery;

interface QueryInterface
{
    /**
     * Gets a raw json string of the query body
     *
     * @param array $values
     * @return string
     * @author Daniel Wendlandt
     */
    public function getRawBody(array $values = array());

    /**
     * Gets an array of the body query
     *
     * @param array $values
     * @return array
     * @author Daniel Wendlandt
     */
    public function getBody(array $values = array());
}