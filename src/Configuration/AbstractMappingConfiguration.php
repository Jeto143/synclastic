<?php

namespace Jeto\Synclastic\Configuration;

use Jeto\Synclastic\Index\DataChange\DataChangeFetcherInterface;
use Jeto\Synclastic\Index\DataFetcher\DataFetcherInterface;
use Jeto\Synclastic\Index\Definition\IndexDefinitionInterface;

abstract class AbstractMappingConfiguration
{
    protected string $mappingName;

    protected string $indexName;

    public function __construct(string $mappingName, string $indexName)
    {
        $this->mappingName = $mappingName;
        $this->indexName = $indexName;
    }

    public function getMappingName(): string
    {
        return $this->mappingName;
    }

    public function getIndexName(): string
    {
        return $this->indexName;
    }

    abstract public function toIndexDefinition(): IndexDefinitionInterface;

    abstract public function toDataFetcher(): DataFetcherInterface;

    abstract public function toDataChangeFetcher(): DataChangeFetcherInterface;
}
