<?php

namespace App\Command;

use App\Entity\Event;
use App\Entity\Broker;
use App\Entity\Client;
use App\Entity\Policy;
use League\Csv\Reader;
use App\Entity\Insurer;
use App\Entity\Product;
use App\Entity\Financials;
use Psr\Log\LoggerInterface;
use App\Entity\BaseEntityInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $broker = $this->findOrCreateEntity(new Broker(), $brokerName);
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
        if (empty($clientRef)) {
            throw new \Exception("Missing client reference for policy: $policyNumber");
        }
        $policyType = trim($record['PolicyType']);
        $clientType = trim($record['ClientType']);
        $insurer_name = trim($record['Insurer']);
        $product_name = trim($record['Product']);
        $event_name = trim($record['BusinessEvent']);
        $insurerPolicyNumber = trim($record['InsurerPolicyNumber']);
        $businessDescription = trim($record['BusinessDescription']);
        $rootPolicyRef = trim($record['RootPolicyRef']);
        $insuredAmount = floatval($record['InsuredAmount']);
        $premium = floatval($record['Premium']);
        $commission = floatval($record['Commission']);
        $adminFee = floatval($record['AdminFee']);
        $iPTAmount = floatval($record['IPTAmount']);
        $policyFee = floatval($record['PolicyFee']);

        $startDate = $this->parseDate($record['StartDate']);
        $endDate = $this->parseDate($record['EndDate']);
        $effectiveDate = $this->parseDate($record['EffectiveDate']);
        $renewalDate = $this->parseDate($record['RenewalDate'], true);
        
        // Ensure required fields exist
        if (!$policyNumber || !$clientRef || !$startDate || !$endDate) {
            throw new \Exception("Missing required fields for policy $policyNumber");
        }

        $client = $this->findOrCreateClient($clientRef, $clientType, $broker);
        if (!$client || !$client->getId()) {
            throw new \Exception("Client creation failed for reference: $clientRef");
        }
        $this->logger->info("Using Client ID: {$client->getId()} for Policy: $policyNumber");
        
        $this->logger->info("Using Client ID: {$client->getId()} for Policy: $policyNumber");
        $insurer = $this->findOrCreateEntity(new Insurer(), $insurer_name, $broker);
        $product = $this->findOrCreateEntity(new Product(), $product_name, $broker);
        $event = $this->findOrCreateEntity(new Event(), $event_name, $broker);
   
        // Check for existing policy
        $policyRepo = $this->entityManager->getRepository(Policy::class);
        $existingPolicy = $policyRepo->findOneBy(['policy_number' => $policyNumber, 'broker' => $broker]);

        if ($existingPolicy) {
            return;
        }

        // Create new Policy
        $policy = new Policy();
        $policy->setPolicyNumber($policyNumber)
            ->setInsurerPolicyNumber($insurerPolicyNumber)
            ->setRootPolicyRef($rootPolicyRef)
            ->setPolicyType($policyType)
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setEffectiveDate($effectiveDate)
            ->setRenewalDate($renewalDate)
            ->setBusinessDescription($businessDescription)
            ->setClient($client)
            ->setInsurer($insurer)
            ->setBroker($broker)
            ->setProduct($product)
            ->setEvent($event);
    
        $this->entityManager->persist($policy);

        // Create Financials entry
        $financials = new Financials();
        $financials->setPolicy($policy);
        $financials->setBroker($broker);
        $financials->setInsuredAmount($insuredAmount);
        $financials->setPremium($premium);
        $financials->setCommission($commission);
        $financials->setAdminFee($adminFee);
        $financials->setIptaAmount($iPTAmount);
        $financials->setPolicyFee($policyFee);

        $this->entityManager->persist($financials);
    }

    private function findOrCreateClient(string $clientRef, string $clientType, Broker $broker): Client
    {
        $this->logger->info("Searching for client: $clientRef under broker: {$broker->getName()}");

        $client = $this->entityManager->getRepository(Client::class)->findOneBy([
            'client_ref' => $clientRef,
            'broker' => $broker
        ]);

        if (!$client) {
            $this->logger->info("Client not found, creating new client: $clientRef");

            $client = new Client();
            $client->setClientRef($clientRef);
            $client->setBroker($broker);
            $client->setClientType($clientType);

            $this->entityManager->persist($client);
            $this->entityManager->flush();

            $this->logger->info("New client created with ID: {$client->getId()}");
        }

        if (!$client || !$client->getId()) {
            throw new \Exception("Failed to create or retrieve client for reference: $clientRef");
        }

        return $client;
    }

    private function findOrCreateEntity(BaseEntityInterface $entity, string $entityName, ?Broker $broker = null): BaseEntityInterface
    {
        $entityClass = get_class($entity);
    
        $this->logger->info("Searching for $entityClass with name: $entityName" . ($broker ? " under broker: {$broker->getName()}" : ""));
    
        $criteria = ['name' => $entityName];
    
        // Ensure broker is added if required
        if ($broker !== null && property_exists($entityClass, 'broker')) {
            $criteria['broker'] = $broker;
        }
    
        $repository = $this->entityManager->getRepository($entityClass);
        $existingEntity = $repository->findOneBy($criteria);
    
        if (!$existingEntity) {
            $this->logger->info("$entityClass not found, creating new instance: $entityName");
    
            $newEntity = new $entityClass();
            $newEntity->setName($entityName);
    
            // Set broker only if the entity supports it
            if ($broker !== null && property_exists($entityClass, 'broker')) {
                $newEntity->setBroker($broker);
            }
    
            $this->entityManager->persist($newEntity);
            $this->entityManager->flush();
    
            return $newEntity;
        }
    
        return $existingEntity;
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
