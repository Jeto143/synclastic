<?php

/**
 * https://www.reddit.com/r/PHP/comments/2k73l9/whats_the_correct_way_of_namespacing_tests/
 * https://github.com/brick
 * https://github.com/DirectoryLister/DirectoryLister
 * https://stackoverflow.com/questions/6520999/create-table-if-not-exists-equivalent-in-sql-server?answertab=votes#tab-top
 * TODO: replace .htaccess (for xdebug) with proper stuff: https://dev.to/_mertsimsek/using-xdebug-with-docker-2k8o
 * TODO: db_change#object_id should not be an int (so as to accept other PK types)
 * TODO: MappingConfiguration
 * TODO: github repo
 * TODO: initial table->document indexing (select all -> insert)
 * TODO: inject mass data into person table
 */

use Elasticsearch\ClientBuilder;
use Jeto\Elasticize\DatabaseTriggerCreator\MysqlDatabaseTriggerCreator;
use Jeto\Elasticize\IndexBuilder\IndexBuilder;
use Jeto\Elasticize\IndexSynchronizer\IndexSynchronizer;
use Jeto\Elasticize\Searcher\Searcher;

require 'vendor/autoload.php';

$pdo = new PDO('mysql:host=db', 'root', 'asdf007', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_CASE => PDO::CASE_NATURAL
]);

$elastic = ClientBuilder::create()->setHosts(['http://elasticsearch:9200'])->build();

$databaseName = 'jeto';
$tableName = 'person';

$indexBuilder = new IndexBuilder($elastic, $pdo);
$indexBuilder->buildIndex($databaseName, $tableName);

$databaseTriggerCreator = new MysqlDatabaseTriggerCreator($pdo);
$databaseTriggerCreator->createDatabaseTriggers($databaseName, $tableName);

$indexSynchronizer = new IndexSynchronizer($elastic, $pdo);
$indexSynchronizer->populateIndices($databaseName, $tableName);

//$indexSynchronizer->synchronizeIndices($databaseName);

$searcher = new Searcher($elastic);

$hits = $searcher->search($tableName, 'Touati');
echo '<pre>';
var_dump($hits);
echo '</pre>';
