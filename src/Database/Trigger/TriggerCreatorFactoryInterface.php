<?php

namespace Jeto\Sqlastic\Database\Trigger;

interface TriggerCreatorFactoryInterface
{
    public function create(\PDO $pdo): TriggerCreatorInterface;
}
