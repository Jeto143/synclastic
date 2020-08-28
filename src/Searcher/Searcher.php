<?php

namespace Jeto\Elasticize\Searcher;

use Elasticsearch\Client as ElasticClient;

final class Searcher implements SearcherInterface
{
    private ElasticClient $elastic;

    public function __construct(ElasticClient $elastic)
    {
        $this->elastic = $elastic;
    }

    public function search(string $indexName, string $query): array
    {
        $response = $this->elastic->search([
            'index' => $indexName,
            'body' => [
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => ['*']
                    ]
                ]
            ]
        ]);

        return array_map(static function (array $hit): \stdClass {
            return (object)$hit['_source'];
        }, $response['hits']['hits']);
    }
}
