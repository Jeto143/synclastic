<?php

namespace Jeto\Sqlastic\ConsoleCommand;

use Jeto\Sqlastic\Index\Operation\Operation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument(
                'mapping_names',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Which mappings?'
            )
        ;
    }

    protected function fetchConfigurationData(InputInterface $input): \stdClass
    {
        return Yaml::parseFile('config.yml', Yaml::PARSE_OBJECT_FOR_MAP);
    }


    protected function fetchMappingNames(\stdClass $configData, InputInterface $input): array
    {
        $mappingNames = $input->getArgument('mapping_names');

        return $mappingNames ?: array_keys((array)$configData->mappings);
    }

    protected function computeOperationText(Operation $operation): string
    {
        switch ($operation->getType()) {
            case Operation::TYPE_ADD:
                $actionString = 'Added';
                break;
            case Operation::TYPE_DELETE:
                $actionString = 'Deleted';
                break;
            case Operation::TYPE_UPDATE:
            default:
                $actionString = 'Updated';
        }

        return "[{$operation->getIndexName()}] {$actionString} document {$operation->getDocumentIdentifier()}";
    }
}
