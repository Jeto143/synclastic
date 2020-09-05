<?php

use Elasticsearch\ClientBuilder;
use Jeto\Sqlastic\Database\ConnectionSettings;
use Jeto\Sqlastic\Database\Introspection\PgsqlDatabaseIntrospector;
use Jeto\Sqlastic\Database\Trigger\MysqlTriggerCreator;
use Jeto\Sqlastic\Database\Trigger\PgsqlTriggerCreator;
use Jeto\Sqlastic\Index\Builder\IndexBuilder;
use Jeto\Sqlastic\Index\Populator\IndexPopulator;
use Jeto\Sqlastic\Index\Synchronizer\IndexSynchronizer;
use Jeto\Sqlastic\Mapping\BasicMapping;

require 'vendor/autoload.php';

//$pdo = new PDO('pgsql:host=pgsql;port=5432', 'postgres', 'asdf007', [
//    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//    PDO::ATTR_EMULATE_PREPARES => false,
////    PDO::ATTR_CASE => PDO::CASE_NATURAL
//]);

$connectionSettings = new ConnectionSettings('pgsql', 'pgsql', 'postgres', 'asdf007');

$elastic = ClientBuilder::create()->setHosts(['http://elasticsearch:9200'])->build();

$databaseName = 'public';
$tableName = 'actor';
$indexName = 'actor';

$mapping = new class ($databaseName, $tableName, $indexName, new PgsqlDatabaseIntrospector(
    $connectionSettings
)) extends BasicMapping {
    public function getComputedFieldsMappings(): array
    {
        return [];
    }
};

//$elastic->bulk(
//    [
//        'body' =>
//            [
//                [
//                    'index' =>
//                        [
//                            '_index' => 'actor',
//                            '_id' => 2,
//                        ],
//                ],
//                [
//                    'last_update' => strtotime('2006-02-15 04:34:33'),
//                    'first_name' => 'NICK',
//                    'last_name' => 'WAHLBERG',
//                ],
//            ],
//    ]
//);
//die;

//$mappingConfiguration = new MappingConfiguration([$mapping]);

//(new IndexBuilder($elastic))->buildIndex($mapping);
//(new PgsqlTriggerCreator($connectionSettings))->createDatabaseTriggers([$mapping], true);
(new IndexPopulator($elastic, $connectionSettings))->populateIndex($mapping);
