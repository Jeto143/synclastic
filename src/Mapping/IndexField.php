<?php

namespace Jeto\Sqlastic\Mapping;

final class IndexField
{
    private string $name;
    private string $type;
    private ?string $sourceType;

    public function __construct(string $name, string $type, ?string $sourceType = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->sourceType = $sourceType;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }
}
