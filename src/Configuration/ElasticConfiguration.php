<?php

namespace Jeto\Synclastic\Configuration;

use Elasticsearch\Client as ElasticClient;
use Elasticsearch\ClientBuilder as ElasticClientBuilder;

final class ElasticConfiguration
{
    private string $serverUrl;

    public function __construct(string $serverUrl)
    {
        $this->serverUrl = $serverUrl;
    }

    public function getServerUrl(): string
    {
        return $this->serverUrl;
    }

    public function toElasticClient(): ElasticClient
    {
        return ElasticClientBuilder::create()->setHosts((array)$this->serverUrl)->build();
    }
}
