<?php

namespace Jeto\Synclastic\Database\DataConverter;

use Jeto\Synclastic\Database\DatabaseConnectionSettings;

final class DataConverterFactory
{
    public function create(DatabaseConnectionSettings $connectionSettings): ?DataConverterInterface
    {
        $driverName = $connectionSettings->getDriverName();

        switch ($driverName) {
            case 'pgsql':
                return new PgsqlDataConverter();
            default:
                return null;
        }
    }
}
