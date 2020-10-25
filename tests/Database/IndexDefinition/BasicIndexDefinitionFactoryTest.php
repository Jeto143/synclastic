<?php

namespace Jeto\Synclastic\Tests\Database\IndexDefinition;

use Jeto\Synclastic\Database\IndexDefinition\BasicIndexDefinitionFactory;
use Jeto\Synclastic\Database\Introspector\DatabaseIntrospectorInterface;
use Jeto\Synclastic\Database\Mapping\DatabaseMappingInterface;
use Jeto\Synclastic\Tests\Database\TestCase as DatabaseTestCase;

final class BasicIndexDefinitionFactoryTest extends DatabaseTestCase
{
    // TODO: unfinished
    public function testCreate(): void
    {
        $databaseIntrospector = $this->createStub(DatabaseIntrospectorInterface::class);
        $databaseIntrospector->method('fetchPrimaryKeyName')->willReturn('id');
        $databaseIntrospector->method('fetchColumnsTypes')->willReturn([
            'id' => 'int',
            'name' => 'varchar',
            'foo' => 'bar'
        ]);

        $databaseMapping = $this->createStub(DatabaseMappingInterface::class);
        $databaseMapping->method('getDatabaseName')->willReturn('testdb');
        $databaseMapping->method('getTableName')->willReturn('testtable');
        $databaseMapping->method('getIndexName')->willReturn('testindex');
        $databaseMapping->method('getDatabaseName')->willReturn('testdb');

        $basicIndexDefinitionFactory = new BasicIndexDefinitionFactory($databaseIntrospector);
        $indexDefinition = $basicIndexDefinitionFactory->create('testindex', $databaseMapping);

        self::assertSame('testindex', $indexDefinition->getIndexName());
        self::assertSame('id', $indexDefinition->getIdentifierFieldName());
    }
}
