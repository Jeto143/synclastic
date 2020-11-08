<?php

namespace Jeto\Synclastic\ConsoleCommand;

use Jeto\Synclastic\Index\Builder\IndexBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class BuildCommand extends AbstractCommand
{
    protected static $defaultName = 'build';

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
                "<question>This might reindex the [{$indexDefinition->getIndexName()}] index if it already exists. Are you sure? (y/n)</question> ",
                false
            );
            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }

            $output->writeln("<comment>- [{$mappingName}] Building...</comment>");

            $indexBuilder = new IndexBuilder($configuration->getElasticConfiguration()->toElasticClient());
            $indexBuilder->buildIndex($indexDefinition);

            $output->writeln("<info>- [{$mappingName}] Building successful.</info>");
        }

        $output->writeln('<info>Operation successful.</info>');

        return Command::SUCCESS;
    }
}
