<?php

namespace Jeto\Synclastic\Index\DataChange;

final class DataChange
{
    private int $id;
    private string $objectType;
    // FIXME: mixed
    private string $objectId;

    public static function create(int $id, string $objectType, string $objectId): DataChange
    {
        $dataChange = new self();
        $dataChange->id = $id;
        $dataChange->objectType = $objectType;
        $dataChange->objectId = $objectId;

        return $dataChange;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    // FIXME: mixed
    public function getObjectId(): string
    {
        return $this->objectId;
    }
}
