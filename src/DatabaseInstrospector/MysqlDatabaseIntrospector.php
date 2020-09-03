<?php

namespace Jeto\Elasticize\DatabaseInstrospector;

final class MysqlDatabaseIntrospector implements DatabaseIntrospectorInterface
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function fetchPrimaryKeyName(string $databaseName, string $tableName): string
    {
        $sql = sprintf('SHOW KEYS FROM %s WHERE Key_name = \'PRIMARY\'', $databaseName . '.' . $tableName);
        return $this->pdo->query($sql)->fetch(\PDO::FETCH_OBJ)->Column_name;
    }

    public function fetchColumnsTypes(string $databaseName, string $tableName): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * 
            FROM `INFORMATION_SCHEMA`.`COLUMNS` 
            WHERE `TABLE_SCHEMA` = ? AND `TABLE_NAME` = ?');

        $stmt->execute([$databaseName, $tableName]);

        $fieldsTypes = [];

        while ($columnData = $stmt->fetch(\PDO::FETCH_OBJ)) {
            $fieldsTypes[$columnData->COLUMN_NAME] = $columnData->DATA_TYPE;
        }

        return $fieldsTypes;
    }

    public function createDatabaseTriggersFor(string $databaseName, string $tableName): void
    {
        // TODO: Implement createDatabaseTriggersFor() method.
    }
}
