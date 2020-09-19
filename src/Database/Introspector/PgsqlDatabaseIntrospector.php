<?php

namespace Jeto\Sqlastic\Database\Introspector;

final class PgsqlDatabaseIntrospector extends AbstractDatabaseIntrospector
{
    public function fetchPrimaryKeyName(string $databaseName, string $tableName): string
    {
        $sql = <<<SQL
            SELECT a.attname
            FROM   pg_index i
            JOIN   pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
            WHERE  i.indrelid = '{$tableName}'::regclass
            AND    i.indisprimary;
        SQL;

        return $this->pdo->query($sql)->fetch(\PDO::FETCH_OBJ)->attname;
    }

    /**
     * @inheritDoc
     */
    public function fetchColumnsTypes(string $databaseName, string $tableName): array
    {
        $statement = $this->pdo->prepare(<<<SQL
            SELECT column_name, data_type FROM information_schema.columns
            WHERE table_name = ?
        SQL);

        $statement->execute([$tableName]);

        $fieldsTypes = [];

        while ($columnData = $statement->fetch(\PDO::FETCH_OBJ)) {
            $fieldsTypes[$columnData->column_name] = $columnData->data_type;
        }

        return $fieldsTypes;
    }
}
