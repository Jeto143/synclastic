<?php

namespace Jeto\Sqlastic\Mapping;

interface MappingInterface
{
    public function getIndexName(): string;

    /**
     * @return IndexField[]
     */
    public function getIndexFields(): array;

    public function getIdentifierFieldName(): string;

    /**
     * @return iterable|mixed[][]
     */
    public function fetchIndexData(): iterable;

    /**
     * @param mixed $identifier
     * @return mixed[]
     */
    public function fetchDocumentData($identifier): ?array;
}
