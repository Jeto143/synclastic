<?php

namespace Jeto\Synclastic\Tests\Index;

use Elasticsearch\Client as ElasticClient;
use Elasticsearch\ClientBuilder as ElasticClientBuilder;

class TestCase extends \PHPUnit\Framework\TestCase
{
    private const ELASTIC_URL = 'http://elasticsearch:9200';

    protected function createElasticClient(): ElasticClient
    {
        return ElasticClientBuilder::create()->setHosts([self::ELASTIC_URL])->build();
    }
}
