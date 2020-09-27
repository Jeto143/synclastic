<?php

namespace Jeto\Synclastic\Index\Definition;

interface DefinitionInterface
{
    public function getIndexName(): string;

    /**
     * @return Field[]
     */
    public function getIndexFields(): array;

    public function getIdentifierFieldName(): string;
}
