<?php

namespace Jeto\Synclastic\Configuration;

final class ConfigurationLoader
{
    public function load(array $configData): Configuration
    {
        $elasticConfiguration = new ElasticConfiguration($configData['elastic']['serverUrl']);

        $databaseConnectionsConfigurations = [];
        foreach ($configData['databaseConnections'] as $databaseConnectionName => $databaseConnectionDesc) {
            $databaseConnectionsConfigurations[$databaseConnectionName] = new DatabaseConnectionConfiguration(
                $databaseConnectionDesc['driver'],
                $databaseConnectionDesc['hostname'],
                $databaseConnectionDesc['port'] ?? null,
                $databaseConnectionDesc['username'],
                $databaseConnectionDesc['password']
            );
        }

        $mappingsConfigurations = array_map(
            static function (string $mappingName, array $mappingDesc) use ($databaseConnectionsConfigurations) {
                $databaseConnectionConfig = $databaseConnectionsConfigurations[$mappingDesc['databaseConnection']];

                $fieldsConfigurations = array_map(
                    static function (string $fieldName, array $fieldDesc) use ($mappingDesc) {
                        if ($fieldDesc['type'] === 'nested') {
                            $nestedFieldsConfigurations = array_map(
                                static function (string $nestedFieldName, array $nestedFieldDesc) {
                                    return new DatabaseBasicFieldConfiguration(
                                        $nestedFieldName,
                                        $nestedFieldDesc['type']
                                    );
                                },
                                array_keys($fieldDesc['fields']),
                                $fieldDesc['fields']
                            );

                            return new DatabaseNestedArrayFieldConfiguration(
                                $fieldName,
                                $fieldDesc['type'],
                                $fieldDesc['databaseName'] ?? $mappingDesc['databaseName'],
                                $fieldDesc['tableName'],
                                $nestedFieldsConfigurations,
                                $fieldDesc['valuesQuery'],
                                $fieldDesc['ownerIdQuery']
                            );
                        }

                        if (isset($fieldDesc['tableName'], $fieldDesc['valueQuery'], $fieldDesc['ownerIdQuery'])) {
                            return new DatabaseComputedFieldConfiguration(
                                $fieldName,
                                $fieldDesc['type'],
                                $fieldDesc['databaseName'] ?? $mappingDesc['databaseName'],
                                $fieldDesc['tableName'],
                                $fieldDesc['valueQuery'],
                                $fieldDesc['ownerIdQuery']
                            );
                        }

                        return new DatabaseBasicFieldConfiguration($fieldName, $fieldDesc['type']);
                    },
                    array_keys($mappingDesc['fields']),
                    $mappingDesc['fields']
                );

                return new DatabaseMappingConfiguration(
                    $mappingName,
                    $mappingDesc['indexName'] ?? $mappingName,
                    $databaseConnectionConfig,
                    $mappingDesc['databaseName'],
                    $mappingDesc['tableName'],
                    $mappingDesc['fieldMappingStrategy'] === 'manual'
                        ? DatabaseMappingConfiguration::FIELD_MAPPING_STRATEGY_MANUAL
                        : DatabaseMappingConfiguration::FIELD_MAPPING_STRATEGY_AUTOMATIC,
                    $fieldsConfigurations
                );
            },
            array_keys($configData['mappings']),
            $configData['mappings']
        );

        return new Configuration(
            $elasticConfiguration,
            $mappingsConfigurations,
            [],
            $databaseConnectionsConfigurations
        );
    }
}
