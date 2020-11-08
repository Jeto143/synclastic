<?php

namespace Jeto\Synclastic\Configuration;

final class Configuration
{
    private ElasticConfiguration $elasticConfiguration;

    /** @var AbstractMappingConfiguration[] */
    private array $mappingsConfigurations;

    /** @var DataChangeFetcherConfiguration[] */
    private array $dataChangeFetchersConfigurations;

    /** @var DatabaseConnectionConfiguration[] */
    private array $databaseConnectionsConfigurations;

    public function __construct(
        ElasticConfiguration $elasticConfiguration,
        array $mappingsConfigurations,
        array $dataChangeFetchersConfigurations,
        array $databaseConnectionsConfigurations
    ) {
        $this->elasticConfiguration = $elasticConfiguration;
        $this->mappingsConfigurations = $mappingsConfigurations;
        $this->dataChangeFetchersConfigurations = $dataChangeFetchersConfigurations;
        $this->databaseConnectionsConfigurations = $databaseConnectionsConfigurations;
    }

    public function getElasticConfiguration(): ElasticConfiguration
    {
        return $this->elasticConfiguration;
    }

    public function getMappingsConfigurations(): array
    {
        return $this->mappingsConfigurations;
    }

    public function getDataChangeFetchersConfigurations(): array
    {
        return $this->dataChangeFetchersConfigurations;
    }

    public function getDatabaseConnectionsConfigurations(): array
    {
        return $this->databaseConnectionsConfigurations;
    }

    public function getMappingConfiguration(string $mappingName): AbstractMappingConfiguration
    {
        foreach ($this->mappingsConfigurations as $mappingConfiguration) {
            if ($mappingConfiguration->getMappingName() === $mappingName) {
                return $mappingConfiguration;
            }
        }

        throw new \InvalidArgumentException("No such mapping: {$mappingName}.");
    }
}
