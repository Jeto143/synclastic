<?php

namespace Jeto\Synclastic\Index\Definition;

interface IndexDefinitionInterface
{
    public function getIndexName(): string;

    /**
     * @return IndexField[]
     */
    public function getIndexFields(): array;

    public function getIdentifierFieldName(): string;
}
