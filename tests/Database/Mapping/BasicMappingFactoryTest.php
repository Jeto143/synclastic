<?php

namespace Jeto\Synclastic\Tests\Database\Mapping;

use Jeto\Synclastic\Database\Introspector\DatabaseIntrospectorInterface;
use Jeto\Synclastic\Database\Mapping\BasicFieldMapping;
use Jeto\Synclastic\Database\Mapping\BasicMappingFactory;
use Jeto\Synclastic\Tests\Database\TestCase as DatabaseTestCase;

final class BasicMappingFactoryTest extends DatabaseTestCase
{
    public function testCreate(): void
    {
        $databaseIntrospector = $this->createStub(DatabaseIntrospectorInterface::class);
        $databaseIntrospector->method('fetchPrimaryKeyName')->willReturn('id');
        $databaseIntrospector->method('fetchColumnsTypes')->willReturn([
            'id' => 'int',
            'name' => 'varchar',
            'foo' => 'bar'
        ]);

        $basicMappingFactory = new BasicMappingFactory($databaseIntrospector);
        $databaseMapping = $basicMappingFactory->create('testdb', 'person', 'test_index_person');

        self::assertSame('testdb', $databaseMapping->getDatabaseName());
        self::assertSame('person', $databaseMapping->getTableName());
        self::assertSame('test_index_person', $databaseMapping->getIndexName());

        $basicFieldMappings = $databaseMapping->getBasicFieldsMappings();
        self::assertCount(3, $basicFieldMappings);
        self::assertContainsEquals(new BasicFieldMapping('id', 'int'), $basicFieldMappings);
        self::assertContainsEquals(new BasicFieldMapping('name', 'varchar'), $basicFieldMappings);
        self::assertContainsEquals(new BasicFieldMapping('foo', 'bar'), $basicFieldMappings);

        self::assertEmpty($databaseMapping->getComputedFieldsMappings());
        self::assertEmpty($databaseMapping->getNestedArrayFieldsMappings());
    }
}
