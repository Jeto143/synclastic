<?php

namespace Jeto\Sqlastic\Database\TriggerCreator;

final class PgsqlTriggerCreator extends AbstractTriggerCreator
{
     /** @inheritDoc */
    public function createDatabaseTriggers(array $mappings, bool $forceReset = false): void
    {
        foreach ($this->computeDataChangeInsertTuples($mappings) as $databaseName => $databaseTuples) {
            $this->createDataChangeTable($databaseName, $forceReset);

            foreach ($databaseTuples as $tableName => $tuples) {
                foreach (['INSERT' => 'NEW', 'UPDATE' => 'NEW', 'DELETE' => 'OLD'] as $action => $tableAlias) {
                    $triggerName = "TR_sqlastic_{$tableName}_{$action}";
                    $procedureName = "sqlastic_on_{$tableName}_{$action}";

                    $tuplesSql = implode(",\n\t\t", $tuples);
                    $tuplesSql = preg_replace('/\bthis(?=\.)/', $tableAlias, $tuplesSql);

                    $this->pdo->exec(<<<SQL
                        DROP TRIGGER IF EXISTS "{$triggerName}" ON "{$databaseName}"."{$tableName}";
                    SQL);

                    $this->pdo->exec(<<<SQL
                        DROP FUNCTION IF EXISTS "{$databaseName}"."{$procedureName}";
                    SQL);

                    $this->pdo->exec(<<<SQL
                    	CREATE FUNCTION "{$databaseName}"."{$procedureName}"() RETURNS trigger
                    	LANGUAGE plpgsql
                    	AS $$
                    	BEGIN
                    		INSERT INTO "data_change" 
                    			("index", "object_type", "object_id") 
                    		VALUES 
                    			{$tuplesSql}
                    		ON CONFLICT DO NOTHING;
                    		RETURN {$tableAlias};
                    	END;
                    	$$;
                    SQL);

                    $this->pdo->exec(<<<SQL
                    	CREATE TRIGGER "{$triggerName}" 
                    	AFTER {$action} ON "{$databaseName}"."{$tableName}" 
                    	FOR EACH ROW 
                    	EXECUTE FUNCTION "{$databaseName}"."{$procedureName}"()
                    SQL);
                }
            }
        }
    }

    private function createDataChangeTable(string $databaseName, bool $forceReset): void
    {
        if ($forceReset) {
            $this->pdo->exec("DROP TABLE IF EXISTS \"{$databaseName}\".\"data_change\"");
        }

        $this->pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS "{$databaseName}"."data_change" (
                "id" SERIAL PRIMARY KEY,
                "index" VARCHAR(255) NOT NULL,
                "object_type" VARCHAR(255) NOT NULL,
                "object_id" INT NOT NULL,
                CONSTRAINT object_unique UNIQUE ("object_type", "object_id") 
            )
        SQL);
    }
}
