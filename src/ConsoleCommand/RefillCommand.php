<?php

namespace Jeto\Synclastic\ConsoleCommand;

use Jeto\Synclastic\Configuration\Configuration;
use Jeto\Synclastic\Index\Refiller\IndexRefiller;
use Jeto\Synclastic\Index\Refiller\IndexRefillerInterface;
use Jeto\Synclastic\Index\Updater\IndexUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class RefillCommand extends AbstractCommand
{
    protected static $defaultName = 'refill';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setDescription('TODO')
            ->setHelp('TODO')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configuration = $this->fetchConfiguration($input);
        $mappingNames = $this->fetchMappingNames($configuration, $input);

        foreach ($mappingNames as $mappingName) {
            $indexDefinition = $configuration->getMappingConfiguration($mappingName)->toIndexDefinition();

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                "<question>This will clear then fill the [{$indexDefinition->getIndexName()}] index again. Are you sure? (y/n)</question> ",
                false
            );

            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }

            $output->writeln("<comment>- [{$mappingName}] Refilling...</comment>");
            $indexRefiller = $this->createIndexRefiller($configuration, $mappingName);

            $operations = $indexRefiller->refillIndex($indexDefinition);
            foreach ($operations as $operation) {
                $output->writeln("<comment>- {$this->computeOperationText($operation)}...");
            }

            $output->writeln("<info>- [{$mappingName}] Refilling successful.</info>");
        }

        $output->writeln('<info>Operation successful.</info>');

        return Command::SUCCESS;
    }

    private function createIndexRefiller(Configuration $configuration, string $mappingName): IndexRefillerInterface
    {
        $elasticClient = $configuration->getElasticConfiguration()->toElasticClient();
        $dataFetcher = $configuration->getMappingConfiguration($mappingName)->toDataFetcher();
        $updater = new IndexUpdater($configuration->getElasticConfiguration()->toElasticClient());

        return new IndexRefiller($elasticClient, $dataFetcher, $updater);
    }
}
