<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 07/10/15
 * Time: 08:48
 */

namespace Elastification\BackupRestore\Tests\Unit\Repository\ElasticQuery\V1x;

use Elastification\BackupRestore\Repository\ElasticQuery\V1x\DocsInIndexTypeQuery;

class DocsInIndexTypeQueryTest extends \PHPUnit_Framework_TestCase
{

    public function testInstance()
    {
        $query = new DocsInIndexTypeQuery();

        $this->assertInstanceOf(
            'Elastification\BackupRestore\Repository\ElasticQuery\QueryInterface',
            $query);

        $this->assertInstanceOf(
            'Elastification\BackupRestore\Repository\ElasticQuery\V1x\DocsInIndexTypeQuery',
            $query);
    }

    public function testGetRawBody()
    {
        $values = ['size' => 100];
        $query = new DocsInIndexTypeQuery();

        $string = $query->getRawBody($values);

        $this->assertContains('"size": ' . $values['size'], $string);
    }

    public function testGetBody()
    {
        $values = ['size' => 100];
        $query = new DocsInIndexTypeQuery();

        $bodyArray = $query->getBody($values);

        $this->assertTrue(is_array($bodyArray));
        $this->assertTrue(isset($bodyArray['aggs']));
        $this->assertTrue(isset($bodyArray['size']));
        $this->assertTrue(isset($bodyArray['aggs']['count_docs_in_index']));
        $subIndex = $bodyArray['aggs']['count_docs_in_index'];
        $this->assertTrue(isset($subIndex['terms']));
        $this->assertTrue(isset($subIndex['terms']['field']));
        $this->assertSame('_index', $subIndex['terms']['field']);
        $this->assertTrue(isset($subIndex['terms']['size']));
        $this->assertSame($values['size'], $subIndex['terms']['size']);
        $this->assertTrue(isset($subIndex['aggs']));
        $this->assertTrue(isset($subIndex['aggs']['count_docs_in_types']));
        $subType = $subIndex['aggs']['count_docs_in_types'];
        $this->assertTrue(isset($subType['terms']));
        $this->assertTrue(isset($subType['terms']['field']));
        $this->assertSame('_type', $subType['terms']['field']);
        $this->assertTrue(isset($subType['terms']['size']));
        $this->assertSame($values['size'], $subType['terms']['size']);
    }

}