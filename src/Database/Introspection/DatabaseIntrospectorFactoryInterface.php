<?php

namespace Jeto\Sqlastic\Database\Introspection;

use Jeto\Sqlastic\Database\ConnectionSettings;

interface DatabaseIntrospectorFactoryInterface
{
    public function create(ConnectionSettings $connectionSettings): DatabaseIntrospectorInterface;
}
