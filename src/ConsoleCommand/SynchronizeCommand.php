<?php

namespace Jeto\Synclastic\ConsoleCommand;

use Jeto\Synclastic\Configuration\Configuration;
use Jeto\Synclastic\Index\Synchronizer\IndexSynchronizer;
use Jeto\Synclastic\Index\Synchronizer\IndexSynchronizerInterface;
use Jeto\Synclastic\Index\Updater\IndexUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class SynchronizeCommand extends AbstractCommand
{
    protected static $defaultName = 'synchronize';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setDescription('TODO')
            ->setHelp('TODO')
            ->addOption(
                'identifiers',
                'i',
                InputOption::VALUE_REQUIRED,
                'Comma-separated list of identifiers'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configuration = $this->fetchConfiguration($input);
        $mappingNames = $this->fetchMappingNames($configuration, $input);
        $identifiers = array_filter(explode(',', $input->getOption('identifiers')));

        foreach ($mappingNames as $mappingName) {
            $indexDefinition = $configuration->getMappingConfiguration($mappingName)->toIndexDefinition();

            $indexSynchronizer = $this->createIndexSynchronizer($configuration, $mappingName);

            if ($identifiers) {
                $commaSeparatedIds = implode(', ', $identifiers);
                $output->writeln("<comment>- [{$mappingName}] Synchronizing entries {$commaSeparatedIds}...</comment>");
                $operations = $indexSynchronizer->synchronizeDocumentsByIds($indexDefinition, $identifiers);
            } else {
                $output->writeln("<comment>- [{$mappingName}] Synchronizing...</comment>");
                $operations = $indexSynchronizer->synchronizeDocuments($indexDefinition);
            }
            foreach ($operations as $operation) {
                $output->writeln("<comment>- {$this->computeOperationText($operation)}...");
            }
            $output->writeln("<info>- [{$mappingName}] Synchronization successful.</info>");
        }

        $output->writeln('<info>Operation successful.</info>');

        return Command::SUCCESS;
    }

    private function createIndexSynchronizer(
        Configuration $configuration,
        string $mappingName
    ): IndexSynchronizerInterface {
        $dataChangeFetcher = $configuration->getMappingConfiguration($mappingName)->toDataChangeFetcher();
        $dataFetcher = $configuration->getMappingConfiguration($mappingName)->toDataFetcher();
        $updater = new IndexUpdater($configuration->getElasticConfiguration()->toElasticClient());

        return new IndexSynchronizer($dataChangeFetcher, $dataFetcher, $updater);
    }
}
