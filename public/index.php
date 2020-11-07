<?php
ini_set('xdebug.var_display_max_depth', '-1');
ini_set('xdebug.var_display_max_children', '-1');
ini_set('xdebug.var_display_max_data', '-1');
/**
 * https://www.reddit.com/r/PHP/comments/2k73l9/whats_the_correct_way_of_namespacing_tests/
 * https://github.com/brick
 * https://github.com/DirectoryLister/DirectoryLister
 * https://stackoverflow.com/questions/6520999/create-table-if-not-exists-equivalent-in-sql-server?answertab=votes#tab-top
 * https://stackoverflow.com/questions/23917327/delete-all-documents-from-index-type-without-deleting-type
 * https://docs.docker.com/network/
 * https://www.elastic.co/guide/en/elasticsearch/guide/master/relations.html
 * https://www.elastic.co/blog/found-keeping-elasticsearch-in-sync
 * https://github.com/thephpleague/event
 * TODO: replace .htaccess (for xdebug) with proper stuff: https://dev.to/_mertsimsek/using-xdebug-with-docker-2k8o
 * TODO: db_change#object_id should not be an int (so as to accept other PK types)
 * TODO: github repo
 * TODO: instead of returning void, return elastic responses when appropriate?
 * TODO: index name should be based on database name + table name (+ optionally some config prefix/suffix)
 * TODO: handle other types in getElasticType (move to dedicated service?), i.e. date
 * TODO: use a cross-DBMS query builder such as Doctrine's?
 * TODO: consider removing all side effets from API by returning arrays of prepared SQL queries / elastic actions
 *
 * TODO: relationships (computed fields) -> https://www.elastic.co/guide/en/elasticsearch/guide/master/relations.html
 * TODO: Trello
 * TODO: MappingConfiguration
 * TODO: https://github.com/myclabs/php-enum
 * TODO: figure out whether/when to use "field" or "property" for elastic
 * TODO: move FieldMapping namespace into Mapping/ ?
 * TODO: remove all "@inheritDoc" except when adding stuff to the doc (see https://youtrack.jetbrains.com/issue/WI-23586)
 * ^^^ "@ineritDoc" is only necessary in a docblock if you are overriding a specific portion of a comment and wish to keep the other portion (e.g., adding a @throws, modifying the description, etc.).: https://youtrack.jetbrains.com/issue/WI-23395
 * TODO: look into psalm
 * TODO: declare(strict_types = 1);
 * TODO: use "true" / immutable DTOs for classes such as BasicFieldMapping / ComputedFieldMapping?
 * TODO: learn more stuff about elastic, "local node", shards,
 *
 * TODO: !! make sure nothing is sql injectable no matter what !! https://stackoverflow.com/a/4978062/965834
 * TODO: mssql
 * TODO: PHPUnit tests
 * TODO: error handling
 * TODO: protect DB identifier service
 * TODO: consider removing factory interfaces: bit too much? if not, add one for each factory if missing
 * TODO: add ability to filter which tuples get synced
 * TODO: populator -> refresher?
 * TODO: have interactive bash commands to execute tasks / import yaml config file
 * TODO: check all classes for final keyword (when necessary)
 * https://matthiasnoback.nl/2020/09/simple-recipe-for-framework-decoupling/
 * synclastic? syncastic? elastisync? maplastic
 * TODO: find a more accurate type hinting for "mixed" identifiers (int|string|\DateTime?)
 * TODO: register yaml mapping type parsers, one for each mapping type
 * TODO: populator -> filler
 * TODO: sometimes methods return keyed arrays, sometimes it doesn't matter, make sure all is right/consistent
 *
 * TODO: CRON sync
 * TODO: volumes section in docker-compose for ES (indices aren't saved between sessions)
 * Rather than fields being either basic or computed, have 2 distinct collections: (basic_)?fields and computed_fields
 * Replicastic? Synclastic
 */

use Elasticsearch\ClientBuilder;
use Jeto\Synclastic\Database\DatabaseConnectionSettings;
use Jeto\Synclastic\Database\DataConverter\DataConverterInterface;
use Jeto\Synclastic\Database\DataFetcher\BasicDataFetcher;
use Jeto\Synclastic\Database\Introspector\DatabaseInstrospectorFactory;
use Jeto\Synclastic\Database\Introspector\DatabaseIntrospectorInterface;
use Jeto\Synclastic\Database\IndexDefinition\BasicIndexDefinitionFactory;
use Jeto\Synclastic\Database\Mapping\BasicFieldMapping;
use Jeto\Synclastic\Database\Mapping\BasicMappingFactory;
use Jeto\Synclastic\Database\DataChangeFetcher\DataChangeFetcher;
use Jeto\Synclastic\Database\Mapping\ComputedFieldMapping;
use Jeto\Synclastic\Database\Mapping\DatabaseMapping;
use Jeto\Synclastic\Database\Mapping\DatabaseMappingInterface;
use Jeto\Synclastic\Database\Mapping\NestedArrayFieldMapping;
use Jeto\Synclastic\Database\PdoFactory;
use Jeto\Synclastic\Database\TriggerCreator\MysqlTriggerCreator;
use Jeto\Synclastic\Index\Builder\IndexBuilder;
use Jeto\Synclastic\Index\Refiller\IndexRefiller;
use Jeto\Synclastic\Index\Synchronizer\IndexSynchronizer;
use Jeto\Synclastic\Index\Updater\IndexUpdater;

require 'vendor/autoload.php';

//$pdo = new PDO('mysql:host=mysql', 'root', 'asdf007', [
//    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//    PDO::ATTR_EMULATE_PREPARES => false,
////    PDO::ATTR_CASE => PDO::CASE_NATURAL
//]);

//$ldap = ldap_connect('openldap', 1389);
//ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
//ldap_bind($ldap, 'cn=admin,dc=example,dc=org', 'adminpassword');

$connectionSettings = new DatabaseConnectionSettings('mysql', 'mysql', 3306, 'root', 'asdf007');
$databaseIntrospector = (new DatabaseInstrospectorFactory())->create($connectionSettings);

(new PdoFactory())->create($connectionSettings);

$elastic = ClientBuilder::create()->setHosts(['http://elasticsearch:9200'])->build();
$databaseName = 'employees';
$tableName = 'employees';
$indexName = 'employees';

$mapping = (new BasicMappingFactory($databaseIntrospector))->create($databaseName, $tableName, $indexName);

$mapping = new DatabaseMapping($databaseName, $tableName, $indexName, $mapping->getBasicFieldsMappings(), [
    new ComputedFieldMapping(
        $databaseName,
        'salaries',
        'SELECT salary FROM employees.salaries s WHERE s.emp_no = :id ORDER BY s.to_date DESC LIMIT 1',
        'this.emp_no',
        'salary',
        'integer'
    )
], [
    new NestedArrayFieldMapping(
        $databaseName,
        'titles',
        [
            new BasicFieldMapping('title', 'varchar')
        ],
        'SELECT title FROM employees.titles t WHERE t.emp_no = :id',
        'this.emp_no',
        'titles'
    )
]);

$indexDefinition = (new BasicIndexDefinitionFactory($databaseIntrospector))->create($indexName, $mapping);

$dataFetcher = new BasicDataFetcher($mapping, $connectionSettings);
$dataChangeFetcher = new DataChangeFetcher($connectionSettings, $databaseName);


$indexUpdater = new IndexUpdater($elastic);
$indexRefiller = new IndexRefiller($elastic, $dataFetcher, $indexUpdater);
$indexSynchronizer = new IndexSynchronizer($dataChangeFetcher, $dataFetcher, $indexUpdater);

$indexSynchronizer->synchronizeDocuments($indexDefinition);
//$indexRefiller->refillIndex($indexDefinition);
die;

echo '<pre>'; var_dump(iterator_to_array($dataFetcher->fetchSourceData($indexDefinition))); echo '</pre>';
die;

$dataConverter = null;//new MysqlDataConverter();
$builder = new IndexBuilder($elastic);

$builder->buildIndex($indexDefinition);

die;
(new MysqlTriggerCreator($connectionSettings))->createDatabaseTriggers([$mapping]);

die;

$dataChangeFetcher = new DataChangeFetcher($connectionSettings, $databaseName);

$fetcher = new class($ldap, $mapping, $connectionSettings, $dataConverter) extends BasicDataFetcher {
    /** @var resource */
    private $ldap;

    public function __construct(
        $ldap,
        DatabaseMappingInterface $databaseMapping,
        DatabaseConnectionSettings $connectionSettings,
        ?DataConverterInterface $dataConverter = null,
        ?DatabaseIntrospectorInterface $databaseIntrospector = null
    ) {
        parent::__construct($databaseMapping, $connectionSettings, $dataConverter, $databaseIntrospector);
        $this->ldap = $ldap;
    }

    public function fetchSourceData(DefinitionInterface $indexDefinition, ?array $identifiers = null): iterable
    {
        $primaryKeyName = $this->databaseIntrospector->fetchPrimaryKeyName(
            $this->databaseMapping->getDatabaseName(),
            $this->databaseMapping->getTableName()
        );

        foreach (parent::fetchSourceData($indexDefinition, $identifiers) as $rowData) {
            $identifier = $rowData[$primaryKeyName];
            $rowData['telephoneNumber'] = $this->fetchTelephoneNumber($identifier);

            yield $rowData;
        }
    }

    private function fetchTelephoneNumber(int $identifier): ?string
    {
        $result = ldap_search($this->ldap, 'dc=example,dc=org', "(sn={$identifier})");
        $data = ldap_get_entries($this->ldap, $result);

        return $data[0]['telephonenumber'][0] ?? null;
    }
};

$updater = new Updater($elastic);
$synchronizer = new Synchronizer($dataChangeFetcher, $fetcher, $updater);
$filler = new Refiller($elastic, $fetcher, $updater);

//(new MysqlTriggerCreator($connectionSettings))->createDatabaseTriggers([$mapping]);

//(new IndexBuilder($elastic))->buildIndex($mapping);

//$filler->refillIndex($indexDefinition);

//(new Filler($elastic, $fetcher, $updater))->fillIndex($mapping);

//$synchronizer->synchronizeDocumentsByIds($indexDefinition, [10002]);

//$indexUpdater->updateDocuments($mapping, [10002]);

//(new IndexSynchronizer($indexUpdater, $dataChangeProvider))->synchronizeIndex($mapping);
