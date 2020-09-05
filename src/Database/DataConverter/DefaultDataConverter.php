<?php

namespace Jeto\Sqlastic\Database\DataConverter;

class DefaultDataConverter implements DataConverterInterface
{

    /** @inheritDoc */
    public function convertValue(string $type, $value)
    {
        return $value;
    }
}
