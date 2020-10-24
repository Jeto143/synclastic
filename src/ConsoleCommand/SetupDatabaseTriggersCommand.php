<?php

namespace Jeto\Synclastic\ConsoleCommand;

use Jeto\Synclastic\Database\DatabaseConnectionSettings;
use Jeto\Synclastic\Database\Mapping\DatabaseMappingInterface;
use Jeto\Synclastic\Database\TriggerCreator\DatabaseTriggerCreatorFactory;
use Jeto\Synclastic\ServiceBuilder\ServiceBuilder;
use Jeto\Synclastic\ServiceBuilder\ServiceBuilderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class SetupDatabaseTriggersCommand extends AbstractCommand
{
    protected static $defaultName = 'setup-db-triggers';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setDescription('TODO')
            ->setHelp('TODO');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configData = $this->fetchConfigurationData($input);
        $mappingNames = $this->fetchMappingNames($configData, $input);

        $serviceBuilder = new ServiceBuilder($configData);

        $databaseMappingsByConnection = $this->computeDatabaseMappingsByConnection($serviceBuilder, $mappingNames);

        if ($databaseMappingsByConnection) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                "<question>This will create/update database triggers again. Are you sure? (y/n)</question> ",
                false
            );

            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }

            /** @var DatabaseConnectionSettings $connectionSettings */
            foreach ($databaseMappingsByConnection as $connectionSettings) {
                /** @var DatabaseMappingInterface[] $databaseMappings */
                $databaseMappings = $databaseMappingsByConnection[$connectionSettings];

                $indexNames = implode(
                    ', ',
                    array_map(
                        static fn(DatabaseMappingInterface $mapping) => $mapping->getIndexName(),
                        $databaseMappings
                    )
                );

                $output->writeln("<comment>- [{$indexNames}] Creating/updating triggers...</comment>");

                $triggerCreator = (new DatabaseTriggerCreatorFactory())->create($connectionSettings);
                $triggerCreator->createDatabaseTriggers($databaseMappings);

                $output->writeln("<info>- [{$indexNames}] Creating/updating triggers successful.</info>");
            }

            $output->writeln('<info>Operation successful.</info>');
        }

        return Command::SUCCESS;
    }

    private function computeDatabaseMappingsByConnection(
        ServiceBuilderInterface $serviceBuilder,
        array $mappingNames
    ): \SplObjectStorage {
        $databaseMappingsByConnection = new \SplObjectStorage();

        foreach ($mappingNames as $mappingName) {
            $connectionSettings = $serviceBuilder->buildDatabaseConnectionSettings($mappingName);
            if ($databaseMappingsByConnection->offsetExists($connectionSettings)) {
                $mappings = $databaseMappingsByConnection[$connectionSettings];
            } else {
                $mappings = [];
            }
            $mappings[] = $serviceBuilder->buildDatabaseMapping($mappingName);
            $databaseMappingsByConnection[$connectionSettings] = $mappings;
        }

        return $databaseMappingsByConnection;
    }
}
