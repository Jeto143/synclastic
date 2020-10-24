<?php

namespace Jeto\Synclastic\Database\DataChangeFetcher;

use Jeto\Synclastic\Database\DatabaseConnectionSettings;
use Jeto\Synclastic\Database\PdoFactory;
use Jeto\Synclastic\Index\DataChange\DataChange;
use Jeto\Synclastic\Index\DataChange\DataChangeFetcherInterface;
use Jeto\Synclastic\Index\Definition\IndexDefinitionInterface;

final class DataChangeFetcher implements DataChangeFetcherInterface
{
    private \PDO $pdo;

    private string $databaseName;

    public function __construct(DatabaseConnectionSettings $connectionSettings, string $databaseName)
    {
        $this->pdo = (new PdoFactory())->create($connectionSettings);
        $this->databaseName = $databaseName;
    }

    public function fetchDataChanges(IndexDefinitionInterface $indexDefinition): array
    {
        $statement = $this->pdo->prepare(<<<SQL
            SELECT "id", "object_type" AS "objectType", "object_id" AS "objectId"
            FROM "{$this->databaseName}"."data_change" 
            WHERE "index" = ?
        SQL);

        $statement->execute([$indexDefinition->getIndexName()]);

        return $statement->fetchAll(\PDO::FETCH_CLASS, DataChange::class);
    }

    public function onDataChangesProcessed(array $dataChanges): void
    {
        if (!$dataChanges) {
            return;
        }

        $dataChangesIds = array_map(static fn(DataChange $dataChange) => $dataChange->getId(), $dataChanges);

        $in  = str_repeat('?,', count($dataChangesIds) - 1) . '?';
        $this->pdo
            ->prepare("DELETE FROM \"{$this->databaseName}\".\"data_change\" WHERE \"id\" = {$in}")
            ->execute($dataChangesIds);
    }
}
