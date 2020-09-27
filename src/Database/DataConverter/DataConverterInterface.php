<?php

namespace Jeto\Synclastic\Database\DataConverter;

interface DataConverterInterface
{
    /**
     * @param mixed $value
     * @return mixed
     */
    public function convertValue(string $type, $value);
}
