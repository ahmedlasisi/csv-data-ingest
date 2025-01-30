<?php

namespace App\Command;

use App\Entity\Broker;
use App\Entity\Policy;
use App\Entity\Client;
use App\Entity\Insurer;
use App\Entity\Product;
use App\Entity\Event;
use App\Entity\Financials;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-policies',
    description: 'Imports insurance policies from broker CSV files'
)]

class ImportPoliciesCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private string $dataDirectory;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->dataDirectory =  $this->dataDirectory = __DIR__ . '/../../var/data'; // Uses Symfony's var/data
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Starting Policy Data Import');

        $brokerFiles = [
            'broker1.csv' => 'Broker One',
            // 'broker2.csv' => 'Broker Two',
        ];

        foreach ($brokerFiles as $file => $brokerName) {
            $filePath = $this->dataDirectory . '/' . $file;

            if (!file_exists($filePath)) {
                $io->warning("File not found: $filePath");
                $this->logger->warning("File not found: $filePath");
                continue;
            }

            $this->processFile($filePath, $brokerName, $io);
        }

        $io->success('Policy data import completed.');
        return Command::SUCCESS;
    }
    
    private function processFile(string $filePath, string $brokerName, SymfonyStyle $io): void
    {
        $io->section("Processing file: " . basename($filePath));

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();
        
        $broker = $this->findOrCreateBroker($brokerName);
        $batchSize = 50;
        $i = 0;

        $this->entityManager->beginTransaction(); // Begin transaction

        try {
            foreach ($records as $record) {
                $this->processRecord($record, $broker);
                
                if (($i % $batchSize) === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
                $i++;
            }

            $this->entityManager->flush();
            $this->entityManager->commit(); // Commit transaction
            $io->success("Finished processing " . basename($filePath));
        } catch (\Exception $e) {
            $this->entityManager->rollback(); // Rollback on error
            $this->logger->error("Failed processing {$filePath}: " . $e->getMessage());
            $io->error("Error: " . $e->getMessage());
        }
    }

    private function processRecord(array $record, Broker $broker): void
    {
        $policyNumber = trim($record['PolicyNumber']);
        $clientRef = trim($record['ClientRef']);
        $policyType = trim($record['PolicyType']);
        $ClientType = trim($record['ClientType']);
        $insuredAmount = floatval($record['InsuredAmount']);
        $premium = floatval($record['Premium']);

        $premium = floatval($record['Insurer']);

        $startDate = $this->parseDate($record['StartDate']);
        $endDate = $this->parseDate($record['EndDate']);
        $effectiveDate = $this->parseDate($record['EffectiveDate']);
        $renewalDate = $this->parseDate($record['RenewalDate'], true);
        
        // Ensure required fields exist
        if (!$policyNumber || !$clientRef || !$startDate || !$endDate) {
            throw new \Exception("Missing required fields for policy $policyNumber");
        }

        $client = $this->findOrCreateClient($clientRef, $broker);

        // Check for existing policy
        $policyRepo = $this->entityManager->getRepository(Policy::class);
        $existingPolicy = $policyRepo->findOneBy(['policy_number' => $policyNumber, 'broker' => $broker]);

        if ($existingPolicy) {
            return;
        }

        // Create new Policy
        $policy = new Policy();
        $policy->setPolicyNumber($policyNumber)
            ->setClient($client)
            ->setBroker($broker)
            ->setPolicyType($policyType)
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setEffectiveDate($effectiveDate)
            ->setRenewalDate($renewalDate);
        
        $this->entityManager->persist($policy);

        // Create Financials entry
        $financials = new Financials();
        $financials->setPolicy($policy);
        $financials->setBroker($broker);
        $financials->setInsuredAmount($insuredAmount);
        $financials->setPremium($premium);
        
        $this->entityManager->persist($financials);
    }

    private function findOrCreateBroker(string $name): Broker
    {
        // Normalize name to match how we store it in the database
        $cleanedName = preg_replace('/\s+/', ' ', trim($name));
        $cleanedName = preg_replace('/[^A-Za-z0-9 ]/', '', $cleanedName); // Remove special characters
        $cleanedName = strtoupper($cleanedName);

        //  Find broker by normalized name
        $broker = $this->entityManager->getRepository(Broker::class)->findOneBy(['name' => $cleanedName]);

        $broker = $this->entityManager->getRepository(Broker::class)->findOneBy(['name' => $name]);

        if (!$broker) {
            $broker = new Broker();
            $broker->setName($name);
            // Generate a unique broker code (e.g., BROKER_1, BROKER_2)
            // $code = strtoupper(str_replace(' ', '_', $name)) . '_' . uniqid();
            $this->entityManager->persist($broker);
            $this->entityManager->flush();
        }

        return $broker;
    }

    private function findOrCreateClient(string $clientRef, Broker $broker): Client
    {
        $client = $this->entityManager->getRepository(Client::class)->findOneBy([
            'client_ref' => $clientRef,
            'broker' => $broker
        ]);

        if (!$client) {
            $client = new Client();
            $client->setClientRef($clientRef);
            $client->setBroker($broker);
            $client->setClientType('Individual');
            $this->entityManager->persist($client);
            $this->entityManager->flush();
        }

        return $client;
    }

    private function parseDate(string $dateString, bool $allowNull = false): ?\DateTimeInterface
    {
        if (empty($dateString) && $allowNull) {
            return null; // Allow nullable dates
        }

        $formats = ['d/m/Y', 'Y-m-d', 'm/d/Y']; // Support multiple formats

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateString);
            if ($date) {
                return $date;
            }
        }

        throw new \Exception("Invalid date format: $dateString"); // Prevents silent failures
    }
}
