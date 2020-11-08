<?php

namespace Jeto\Synclastic\ConsoleCommand;

use Jeto\Synclastic\Configuration\AbstractMappingConfiguration;
use Jeto\Synclastic\Configuration\Configuration;
use Jeto\Synclastic\Configuration\ConfigurationLoader;
use Jeto\Synclastic\Index\Operation\IndexOperation;
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

    protected function fetchConfiguration(InputInterface $input): Configuration
    {
        $configData = Yaml::parseFile('config.yml', Yaml::PARSE_OBJECT_FOR_MAP);

        return (new ConfigurationLoader())->load($configData);
    }


    protected function fetchMappingNames(Configuration $configuration, InputInterface $input): array
    {
        $mappingNames = $input->getArgument('mapping_names');

        return $mappingNames
            ?: array_map(
                static fn(AbstractMappingConfiguration $mappingConfig) => $mappingConfig->getMappingName(),
                $configuration->getMappingsConfigurations()
            );
    }

    protected function computeOperationText(IndexOperation $operation): string
    {
        switch ($operation->getType()) {
            case IndexOperation::TYPE_ADD:
                $actionString = 'Added';
                break;
            case IndexOperation::TYPE_DELETE:
                $actionString = 'Deleted';
                break;
            case IndexOperation::TYPE_UPDATE:
            default:
                $actionString = 'Updated';
        }

        return "[{$operation->getIndexName()}] {$actionString} document {$operation->getDocumentIdentifier()}";
    }
}
