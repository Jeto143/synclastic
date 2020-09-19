<?php
/**
 * https://www.reddit.com/r/PHP/comments/2k73l9/whats_the_correct_way_of_namespacing_tests/
 * https://github.com/brick
 * https://github.com/DirectoryLister/DirectoryLister
 * https://stackoverflow.com/questions/6520999/create-table-if-not-exists-equivalent-in-sql-server?answertab=votes#tab-top
 * https://stackoverflow.com/questions/23917327/delete-all-documents-from-index-type-without-deleting-type
 * https://docs.docker.com/network/
 * https://www.elastic.co/guide/en/elasticsearch/guide/master/relations.html
 * https://www.elastic.co/blog/found-keeping-elasticsearch-in-sync
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
 *
 * TODO: CRON sync
 * TODO: volumes section in docker-compose for ES (indices aren't saved between sessions)
 * Rather than fields being either basic or computed, have 2 distinct collections: (basic_)?fields and computed_fields
 * Replicastic? SQLastic
 */

use Elasticsearch\ClientBuilder;
use Jeto\Sqlastic\Database\ConnectionSettings;
use Jeto\Sqlastic\Database\Introspector\DatabaseIntrospectorInterface;
use Jeto\Sqlastic\Database\Trigger\MysqlTriggerCreator;
use Jeto\Sqlastic\Index\Builder\IndexBuilder;
use Jeto\Sqlastic\Index\Populator\IndexPopulator;
use Jeto\Sqlastic\Index\Synchronizer\IndexSynchronizer;
use Jeto\Sqlastic\Mapping\Database\BasicDatabaseMapping;
use Jeto\Sqlastic\Mapping\Database\CustomDatabaseMapping;
use Jeto\Sqlastic\Mapping\Database\DatabaseDataChangeProvider;
use Jeto\Sqlastic\Mapping\Database\FieldMapping\ComputedFieldMapping;
use Jeto\Sqlastic\Mapping\IndexField;

require 'vendor/autoload.php';

//$pdo = new PDO('mysql:host=mysql', 'root', 'asdf007', [
//    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//    PDO::ATTR_EMULATE_PREPARES => false,
////    PDO::ATTR_CASE => PDO::CASE_NATURAL
//]);

$ldap = ldap_connect('openldap', 1389);
ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_bind($ldap, 'cn=admin,dc=example,dc=org', 'adminpassword');

$connectionSettings = new ConnectionSettings('mysql', 'mysql', 'root', 'asdf007');

$elastic = ClientBuilder::create()->setHosts(['http://elasticsearch:9200'])->build();

$databaseName = 'employees';
$tableName = 'employees';
$indexName = 'employees';

$mapping = new class ($ldap, $connectionSettings, $databaseName, $tableName, $indexName, [], [
    new ComputedFieldMapping(
        'employees',
        'salaries',
        'SELECT salary FROM employees.salaries s WHERE s.emp_no = :id ORDER BY s.to_date DESC LIMIT 1',
        'this.emp_no',
        'salary',
        'integer'
    )
]) extends CustomDatabaseMapping {
    private $ldap;

    public function __construct(
        $ldap,
        ConnectionSettings $connectionSettings,
        string $databaseName,
        string $tableName,
        string $indexName,
        array $basicFieldsMappings = [],
        array $computedFieldsMappings = [],
        DatabaseIntrospectorInterface $databaseIntrospector = null
    ) {
        parent::__construct(
            $connectionSettings,
            $databaseName,
            $tableName,
            $indexName,
            $basicFieldsMappings,
            $computedFieldsMappings,
            $databaseIntrospector
        );
        
        $this->ldap = $ldap;
    }

    public function getIndexFields(): array
    {
        return array_merge(parent::getIndexFields(), [new IndexField('telephoneNumber', 'text')]);
    }

    public function fetchDocumentData($identifier): ?array
    {
        $documentData = parent::fetchDocumentData($identifier);

        if ($documentData !== null) {
            $documentData['telephoneNumber'] = $this->fetchTelephoneNumber($identifier);
        }

        return $documentData;
    }
    
    public function fetchIndexData(): iterable
    {
        foreach (parent::fetchIndexData() as $documentData) {
            $identifier = (int)$documentData['emp_no'];

            $documentData['telephoneNumber'] = $this->fetchTelephoneNumber($identifier);

            yield $documentData;
        }
    }

    private function fetchTelephoneNumber(int $identifier): ?string
    {
        $result = ldap_search($this->ldap, 'dc=example,dc=org', "(sn={$identifier})");
        $data = ldap_get_entries($this->ldap, $result);

        return $data[0]['telephonenumber'][0] ?? null;
    }
};

$dataChangeProvider = new DatabaseDataChangeProvider($connectionSettings, $databaseName);

(new MysqlTriggerCreator($connectionSettings))->createDatabaseTriggers([$mapping]);

(new IndexBuilder($elastic))->buildIndex($mapping);

$dataConverter = null;//new MysqlDataConverter();

//(new IndexPopulator($elastic, $dataConverter))->populateIndex($mapping);
(new IndexSynchronizer($elastic, $dataChangeProvider, $dataConverter))->synchronizeIndex($mapping);


//$mapping = new class ($databaseName, $tableName, $indexName, new MysqlDatabaseIntrospector($connectionSettings)) extends BasicMapping {
//    public function getComputedFieldsMappings(): array
//    {
//        return [
//            new ComputedFieldMapping(
//                'employees',
//                'salaries',
//                'SELECT salary FROM employees.salaries s WHERE s.emp_no = :id ORDER BY s.to_date DESC LIMIT 1',
//                'this.emp_no',
//                'salary',
//                'integer'
//            )
//        ];
//    }
//};
//
////$mappingConfiguration = new MappingConfiguration([$mapping]);
//
////(new IndexBuilder($elastic))->buildIndex($mapping);
////(new MysqlTriggerCreator($connectionSettings))->createDatabaseTriggers([$mapping], true);
//(new IndexSynchronizer($elastic, $connectionSettings))->clearAndSynchronizeIndex($mapping);
////(new IndexSynchronizer($elastic, $pdo))->synchronizeIndex($mapping);
//
////$searcher = new Searcher($elastic);
////
////$hits = $searcher->search($tableName, 'Touati');
////echo '<pre>';
////var_dump($hits);
////echo '</pre>';
