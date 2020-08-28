<?php

namespace Jeto\Elasticize\DatabaseTriggerCreator;

interface DatabaseTriggerCreatorFactoryInterface
{
    public function create(\PDO $pdo): DatabaseTriggerCreatorInterface;
}
