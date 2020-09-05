<?php

namespace Jeto\Sqlastic\Search;

interface SearcherInterface
{
    public function search(string $indexName, string $query): array;
}
