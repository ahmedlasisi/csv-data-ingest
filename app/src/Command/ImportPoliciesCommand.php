<?php

namespace App\Command;

use App\Service\PolicyImportService;
use App\Logger\ConsolePolicyImportLogger;
use App\Interface\PolicyImportLoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:import-policies',
    description: 'Imports insurance policies from broker CSV files using broker configurations from the database'
)]
class ImportPoliciesCommand extends Command
{
    public function __construct(
        private PolicyImportService $policyImportService,
        private PolicyImportLoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // If ConsolePolicyImportLogger is being used, set SymfonyStyle dynamically
        if ($this->logger instanceof ConsolePolicyImportLogger) {
            $this->logger->setSymfonyStyle($io);
        }

        $io->title('Starting Policy Data Import');

        // Inject logger into the service
        $this->policyImportService->setLogger($this->logger);

        try {
            $this->policyImportService->importPolicies($io);
        } catch (\Exception $e) {
            $io->error("Error: " . $e->getMessage());
            $this->logger->error('Import failed');

            return Command::FAILURE;
        }

        $io->success('Policy data import completed.');
        return Command::SUCCESS;
    }
}
