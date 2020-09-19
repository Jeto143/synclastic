<?php

namespace Jeto\Sqlastic\Mapping;

final class DataChange
{
    private int $id;
    private string $objectType;
    private int $objectId;

    public function getId(): int
    {
        return $this->id;
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    public function getObjectId(): int
    {
        return $this->objectId;
    }
}
