<?php

namespace Jeto\Synclastic\Index\DataChange;

final class DataChange
{
    private int $id;
    private string $objectType;
    // FIXME: mixed
    private int $objectId;

    public function getId(): int
    {
        return $this->id;
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    // FIXME: mixed
    public function getObjectId(): int
    {
        return $this->objectId;
    }
}
