<?php

namespace Jeto\Synclastic\Index\Definition;

final class IndexField
{
    private string $name;

    private string $type;

    /** @var IndexField[] */
    private array $subFields;

    /**
     * @param IndexField[] $subFields
     */
    public function __construct(string $name, string $type, array $subFields)
    {
        $this->name = $name;
        $this->type = $type;
        $this->subFields = $subFields;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSubFields(): array
    {
        return $this->subFields;
    }
}
