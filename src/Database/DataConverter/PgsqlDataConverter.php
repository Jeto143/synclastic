<?php

namespace Jeto\Sqlastic\Database\DataConverter;

use Jeto\Sqlastic\Database\DataConverter\DataConverterInterface;

class PgsqlDataConverter implements DataConverterInterface
{
    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function convertValue(string $type, $value)
    {
        if ($type === 'timestamp without time zone') {
            return (new \DateTimeImmutable($value))->format('c');
        }

        return $value;
    }
}
