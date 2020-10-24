<?php

namespace Jeto\Synclastic\Index\Definition;

class IndexDefinition implements IndexDefinitionInterface
{
    protected string $indexName;

    /** @var IndexField[] */
    protected array $indexFields;

    protected string $identifierFieldName;

    public function __construct(string $indexName, array $indexFields, string $identifierFieldName)
    {
        $this->indexName = $indexName;
        $this->indexFields = $indexFields;
        $this->identifierFieldName = $identifierFieldName;
    }

    public function getIndexName(): string
    {
        return $this->indexName;
    }

    public function getIndexFields(): array
    {
        return $this->indexFields;
    }

    public function getIdentifierFieldName(): string
    {
        return $this->identifierFieldName;
    }
}
