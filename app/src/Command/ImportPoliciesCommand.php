<?php

namespace App\Command;

use Psr\Log\LoggerInterface;
use App\Service\PolicyImportService;
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
    private PolicyImportService $policyImportService;
    private LoggerInterface $logger;

    public function __construct(
        PolicyImportService $policyImportService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->policyImportService = $policyImportService;
        $this->logger = $logger;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Starting Policy Data Import');

        try {
            $this->policyImportService->importPolicies($io);
        } catch (\Exception $e) {
            $io->error("Error: " . $e->getMessage());
            $this->logger->error('Import failed', ['exception' => $e]);
            return Command::FAILURE;
        }

        $io->success('Policy data import completed.');
        return Command::SUCCESS;
    }
}
