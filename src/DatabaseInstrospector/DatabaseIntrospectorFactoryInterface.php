<?php

namespace Jeto\Elasticize\DatabaseInstrospector;

interface DatabaseIntrospectorFactoryInterface
{
    public function create(\PDO $pdo): DatabaseIntrospectorInterface;
}
