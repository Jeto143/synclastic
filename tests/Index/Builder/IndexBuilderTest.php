<?php

namespace Jeto\Synclastic\Tests\Index\Builder;

use Jeto\Synclastic\Index\Builder\IndexBuilder;
use Jeto\Synclastic\Index\Definition\IndexDefinition;
use Jeto\Synclastic\Tests\Index\TestCase as IndexTestCase;

final class IndexBuilderTest extends IndexTestCase
{
    private const INDEX_NAME = 'testindex';

    public function testBuildIndex(): void
    {
//        $elastic = $this->createElasticClient();
//
//        $indexBuilder = new IndexBuilder($elastic);
//
//        $indexDefinition = new IndexDefinition(self::INDEX_NAME, [], 'dummy');
//        $indexBuilder->buildIndex($indexDefinition);


    }

    protected function tearDown(): void
    {
//        $this->createElasticClient()->indices()->delete(['index' => '*']);
    }
}
