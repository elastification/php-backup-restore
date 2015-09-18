<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 16/09/15
 * Time: 10:38
 */

namespace Elastification\BackupRestore\Repository\ElasticQuery\V1x;

use Elastification\BackupRestore\Repository\ElasticQuery\QueryInterface;

class DocsInIndexTypeQuery implements QueryInterface
{

    /**
     * @inheritdoc
     */
    public function getRawBody(array $values = array())
    {
        return $this->createBody($values);
    }

    /**
     * @inheritdoc
     */
    public function getBody(array $values = array())
    {
        return json_decode($this->createBody($values), true);
    }

    /**
     * Creates a json string of the the query body.
     *
     * @param array $values
     * @return string
     * @author Daniel Wendlandt
     */
    protected function createBody(array $values)
    {
        return '
            {
                "aggs": {
                    "count_docs_in_index": {
                        "terms" : { "field" : "_index", "size": ' . (int) $values['size'] . ' },
                        "aggs": {
                            "count_docs_in_types": {
                                "terms" : { "field" : "_type", "size": 100 }
                            }
                        }
                    }

                },
                "size": 0
            }
        ';
    }
}