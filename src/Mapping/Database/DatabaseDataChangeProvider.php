<?php

namespace Jeto\Sqlastic\Mapping\Database;

use Jeto\Sqlastic\Database\ConnectionSettings;
use Jeto\Sqlastic\Database\PdoFactory;
use Jeto\Sqlastic\Mapping\DataChange;
use Jeto\Sqlastic\Mapping\DataChangeProviderInterface;
use Jeto\Sqlastic\Mapping\MappingInterface;

class DatabaseDataChangeProvider implements DataChangeProviderInterface
{
    private \PDO $pdo;

    private string $databaseName;

    public function __construct(
        ConnectionSettings $connectionSettings,
        string $databaseName
    ) {
        $this->pdo = (new PdoFactory())->create($connectionSettings);
        $this->databaseName = $databaseName;
    }

    public function fetchDataChanges(MappingInterface $mapping): array
    {
        $statement = $this->pdo->prepare(<<<SQL
            SELECT "id", "object_type" AS "objectType", "object_id" AS "objectId"
            FROM "{$this->databaseName}"."data_change" 
            WHERE "index" = ?
            ORDER BY "object_type", "object_id"
        SQL);

        $statement->execute([$mapping->getIndexName()]);

        return $statement->fetchAll(\PDO::FETCH_CLASS, DataChange::class);
    }

    public function markDataChangeAsProcessed(DataChange $dataChange): void
    {
        $this->pdo
            ->prepare("DELETE FROM \"{$this->databaseName}\".\"data_change\" WHERE \"id\" = ?")
            ->execute([$dataChange->getId()]);
    }
}
