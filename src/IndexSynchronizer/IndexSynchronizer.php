<?php

namespace Jeto\Elasticize\IndexSynchronizer;

use Elasticsearch\Client as ElasticClient;
use Jeto\Elasticize\DatabaseInstrospector\DatabaseInstrospectorFactory;
use Jeto\Elasticize\DatabaseInstrospector\DatabaseIntrospectorInterface;

final class IndexSynchronizer implements IndexSynchronizerInterface
{
    private ElasticClient $elastic;
    private \PDO $pdo;
    private DatabaseIntrospectorInterface $databaseIntrospector;

    public function __construct(
        ElasticClient $elastic,
        \PDO $pdo,
        DatabaseIntrospectorInterface $databaseIntrospector = null
    ) {
        $this->elastic = $elastic;
        $this->pdo = $pdo;
        $this->databaseIntrospector = $databaseIntrospector ?? (new DatabaseInstrospectorFactory())->create($pdo);
    }

    public function synchronizeIndices(string $databaseName): void
    {
        $dataChanges = $this->queryDataChanges($databaseName);

        $processedChangesIds = [];

        foreach ($dataChanges as $dataChange) {
            $this->synchronizeDocument($databaseName, $dataChange);
            $processedChangesIds[] = $dataChange->getId();
        }

        // FIXME: delete rows instead
        if ($processedChangesIds) {
            $in = str_repeat('?,', count($processedChangesIds) - 1) . '?';
            $this->pdo
                ->prepare("UPDATE data_change SET processed = 1 WHERE id IN ({$in})")
                ->execute($processedChangesIds);
        }
    }

    public function populateIndices(string $databaseName, string $tableName): void
    {
        $rowsData = $this->fetchTableRowsData($databaseName, $tableName);

        $primaryKeyName = $this->databaseIntrospector->fetchPrimaryKeyName($databaseName, $tableName);

        foreach ($rowsData as $rowData) {
            $this->elastic->index([
                'index' => $tableName,
                'id' => $rowData[$primaryKeyName],
                'body' => $rowData
            ]);
        }
    }

    private function synchronizeDocument(string $databaseName, DatabaseDataChange $dataChange): void
    {
        $rowData = $this->fetchTableRowData($databaseName, $dataChange->getObjectType(), $dataChange->getObjectId());

        $indexAndId = [
            'index' => $dataChange->getObjectType(),
            'id' => $dataChange->getObjectId()
        ];

        switch ($dataChange->getAction()) {
            case 'add':
                $this->elastic->index($indexAndId + ['body' => $rowData]);
                break;
            case 'upd':
                $this->elastic->update($indexAndId + ['body' => ['doc' => $rowData]]);
                break;
            case 'del':
                $this->elastic->delete($indexAndId);
                break;
        }
    }

    /**
     * @return DatabaseDataChange[]
     */
    private function queryDataChanges(string $databaseName): array
    {
        $this->pdo->exec("USE {$databaseName}");

        $stmt = $this->pdo->query('
            SELECT id, object_type AS objectType, object_id AS objectId, action, processed 
            FROM data_change 
            WHERE processed = 0');

        return $stmt->fetchAll(\PDO::FETCH_CLASS, DatabaseDataChange::class);
    }

    /**
     * @return mixed[]
     */
    private function fetchTableRowData(string $databaseName, string $tableName, int $rowId): array
    {
        $primaryKeyName = $this->databaseIntrospector->fetchPrimaryKeyName($databaseName, $tableName);

        $sql = sprintf('SELECT * FROM %s WHERE %s = ?', $tableName, $primaryKeyName);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$rowId]);

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    private function fetchTableRowsData(string $databaseName, string $tableName): array
    {
        $sql = sprintf('SELECT * FROM %s', $tableName);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
