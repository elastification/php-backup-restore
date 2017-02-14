<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 16/09/15
 * Time: 10:38
 */

namespace Elastification\BackupRestore\Repository\ElasticQuery\V2x;

use Elastification\BackupRestore\Repository\ElasticQuery\QueryInterface;
use Elastification\BackupRestore\Repository\ElasticQuery\V1x\DocsInIndexTypeQuery as BaseQuery;

class DocsInIndexTypeQuery extends BaseQuery implements QueryInterface
{
}