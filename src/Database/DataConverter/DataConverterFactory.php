<?php

namespace Jeto\Synclastic\Database\DataConverter;

use Jeto\Synclastic\Database\ConnectionSettings;
use Jeto\Synclastic\Database\DataConverter\DataConverterInterface;

final class DataConverterFactory
{
    public function create(ConnectionSettings $connectionSettings): ?DataConverterInterface
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
