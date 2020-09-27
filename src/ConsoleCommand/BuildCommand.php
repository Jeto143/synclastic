<?php

namespace Jeto\Sqlastic\ConsoleCommand;

use Jeto\Sqlastic\ServiceBuilder\ServiceBuilder;
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
        $configData = $this->fetchConfigurationData($input);
        $mappingNames = $this->fetchMappingNames($configData, $input);

        $serviceBuilder = new ServiceBuilder($configData);

        foreach ($mappingNames as $mappingName) {
            $indexDefinition = $serviceBuilder->buildIndexDefinition($mappingName);

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                "<question>This might reindex the [{$indexDefinition->getIndexName()}] index if it already exists. Are you sure? (y/n)</question> ",
                false
            );
            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }

            $output->writeln("<comment>- [{$mappingName}] Building...</comment>");

            $indexBuilder = $serviceBuilder->buildIndexBuilder($mappingName);
            $indexBuilder->buildIndex($indexDefinition);

            $output->writeln("<info>- [{$mappingName}] Building successful.</info>");
        }

        $output->writeln('<info>Operation successful.</info>');

        return Command::SUCCESS;
    }
}
