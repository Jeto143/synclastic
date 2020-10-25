<?php

namespace Jeto\Synclastic\Tests\Database\DataConverter;

use Jeto\Synclastic\Database\DataConverter\DataConverterFactory;
use Jeto\Synclastic\Database\DataConverter\PgsqlDataConverter;
use Jeto\Synclastic\Tests\Database\TestCase as DatabaseTestCase;

final class DataConverterFactoryTest extends DatabaseTestCase
{
    public function testCreate(): void
    {
        $dataConverterFactory = new DataConverterFactory();
        $pgsqlDatabaseConnectionSettings = $this->createPgsqlDatabaseConnectionSettings();
        $dataConverter = $dataConverterFactory->create($pgsqlDatabaseConnectionSettings);

        self::assertInstanceOf(PgsqlDataConverter::class, $dataConverter);
    }
}
