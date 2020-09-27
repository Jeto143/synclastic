<?php

namespace Jeto\Synclastic\Database\Introspector;

final class MysqlDatabaseIntrospector extends AbstractDatabaseIntrospector
{
    public function fetchPrimaryKeyName(string $databaseName, string $tableName): string
    {
        $sql = "SHOW KEYS FROM `{$databaseName}`.`{$tableName}` WHERE `Key_name` = 'PRIMARY'";

        return $this->pdo->query($sql)->fetch(\PDO::FETCH_OBJ)->Column_name;
    }

    public function fetchColumnsTypes(string $databaseName, string $tableName): array
    {
        $statement = $this->pdo->prepare(<<<SQL
            SELECT COLUMN_NAME, DATA_TYPE
            FROM `INFORMATION_SCHEMA`.`COLUMNS` 
            WHERE `TABLE_SCHEMA` = ? AND `TABLE_NAME` = ?
        SQL);

        $statement->execute([$databaseName, $tableName]);

        $fieldsTypes = [];

        while ($columnData = $statement->fetch(\PDO::FETCH_OBJ)) {
            $fieldsTypes[$columnData->COLUMN_NAME] = $columnData->DATA_TYPE;
        }

        return $fieldsTypes;
    }
}
