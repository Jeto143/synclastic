<?php

namespace Jeto\Sqlastic\Index\Operation;

final class Operation
{
    public const TYPE_ADD = 1;
    public const TYPE_UPDATE = 2;
    public const TYPE_DELETE = 3;

    private string $indexName;

    /** @var mixed */
    private $documentIdentifier;

    private int $type;

    private ?array $data;

    public function __construct(string $indexName, $documentIdentifier, int $type, ?array $data = null)
    {
        if (!in_array($type, [self::TYPE_ADD, self::TYPE_UPDATE, self::TYPE_DELETE], true)) {
            throw new \InvalidArgumentException("Invalid operation type: {$type}.");
        }

        $this->indexName = $indexName;
        $this->documentIdentifier = $documentIdentifier;
        $this->type = $type;
        $this->data = $data;
    }

    public function getIndexName(): string
    {
        return $this->indexName;
    }

    public function getDocumentIdentifier()
    {
        return $this->documentIdentifier;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function toElasticOperations(): array
    {
        $indexAndId = ['_index' => $this->indexName, '_id' => $this->documentIdentifier];

        switch ($this->type) {
            case self::TYPE_ADD:
                return [
                    ['index' => $indexAndId],
                    $this->data
                ];
            case self::TYPE_DELETE:
                return ['delete' => $indexAndId];
            case self::TYPE_UPDATE:
            default:
                return [
                    ['update' => $indexAndId],
                    ['doc' => $this->data]
                ];
        }
    }
//
//    public function __toString()
//    {
//        switch ($this->type) {
//            case self::TYPE_ADD:
//                $actionString = 'Add';
//                break;
//            case self::TYPE_DELETE:
//                $actionString = 'Delete';
//                break;
//            case self::TYPE_UPDATE:
//            default:
//                $actionString = 'Update';
//        }
//
//        return "[{$this->indexName}] {$actionString} document {$this->documentIdentifier}";
//    }
}
