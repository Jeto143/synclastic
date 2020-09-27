indices:
    services:
        fields:
            csi:
                type: string
            type:
                type: string
            name:
                type: string


<?php

use Elasticsearch\ClientBuilder;
use Jeto\Sqlastic\Database\ConnectionSettings;
use Jeto\Sqlastic\Database\DataConverter\PgsqlDataConverter;
use Jeto\Sqlastic\Database\TriggerCreator\PgsqlTriggerCreator;
use Jeto\Sqlastic\Database\Mapping\ComputedFieldMapping;


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

$mapping = new \Jeto\Sqlastic\Mapping\Database\BasicDatabaseMapping($connectionSettings, $databaseName, $tableName, $indexName, [
    new ComputedFieldMapping(
        'employees',
        'salaries',
        'SELECT salary FROM employees.salaries s WHERE s.emp_no = :id ORDER BY s.to_date DESC LIMIT 1',
        'this.emp_no',
        'salary',
        'integer'
    )
]);

$dataChangeManager = new DatabaseDataChangeManager($connectionSettings, $databaseName);

(new PgsqlTriggerCreator($connectionSettings))->createDatabaseTriggers([$mapping]);
die;

//(new IndexBuilder2($elastic))->buildIndex($mapping);

$dataConverter = new PgsqlDataConverter();

(new IndexPopulator($elastic, $dataConverter))->populateIndex($mapping);
(new IndexSynchronizer($elastic, $dataChangeManager, $dataConverter))->synchronizeIndex($mapping);

//$mapping = new class ($databaseName, $tableName, $indexName, new PgsqlDatabaseIntrospector(
//    $connectionSettings
//)) extends BasicMapping {
//    public function getComputedFieldsMappings(): array
//    {
//        return [];
//    }
//};
//
////$mappingConfiguration = new MappingConfiguration([$mapping]);
//
////(new IndexBuilder($elastic))->buildIndex($mapping);
////(new PgsqlTriggerCreator($connectionSettings))->createDatabaseTriggers([$mapping], true);
//(new IndexPopulator($elastic, $connectionSettings))->populateIndex($mapping);
