<?php

namespace Jeto\Sqlastic\Database\DataConverter;

final class DataConverterFactory
{
    public function create(string $databaseDriverName): DataConverterInterface
    {
        switch ($databaseDriverName) {
            case 'pgsql':
                return new PgsqlDataConverter();
        }

        return new DefaultDataConverter();
    }
}
