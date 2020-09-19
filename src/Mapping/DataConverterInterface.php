<?php

namespace Jeto\Sqlastic\Mapping;

interface DataConverterInterface
{
    /**
     * @param mixed $value
     * @return mixed
     */
    public function convertValue(string $type, $value);
}
