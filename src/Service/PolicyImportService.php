<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Broker;
use App\Entity\Client;
use App\Entity\Policy;
use League\Csv\Reader;
use App\Entity\Insurer;
use App\Entity\Product;
use App\Entity\Financials;
use App\Entity\BrokerConfig;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class PolicyImportService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private string $dataDirectory;
    private ManagerRegistry $managerRegistry;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ManagerRegistry $managerRegistry
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->managerRegistry = $managerRegistry;
        $this->dataDirectory = __DIR__ . '/../../var/data';
    }

    public function importPolicies($io): void
    {
        $brokerConfigs = $this->entityManager->getRepository(BrokerConfig::class)->findAll();

        if (empty($brokerConfigs)) {
            $io->warning("No broker configurations found in database.");
            return;
        }

        foreach ($brokerConfigs as $config) {
            $this->processBrokerConfig($config, $io);
        }
    }

    private function processBrokerConfig(BrokerConfig $config, $io): void
    {
        $filePath = $this->dataDirectory . '/' . $config->getFileName();
        $broker = $config->getBroker();

        if (!file_exists($filePath)) {
            $io->warning("File not found: $filePath");
            $this->logger->warning("File not found: $filePath");
            return;
        }

        $this->processFile($filePath, $broker, $config, $io);
    }

    private function processFile(string $filePath, Broker $broker, BrokerConfig $config, $io): void
    {
        $io->section("Processing file: " . basename($filePath));
    
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $records = $csv->getRecords();
            $csvHeaders = $csv->getHeader();
    
            $fileMapping = $config->getFileMapping();
    
            if (!$this->validateMapping($fileMapping, $csvHeaders)) {
                throw new \Exception("Invalid JSON mapping for file: " . $config->getFileName());
            }
    
            $batchSize = 50;
            $i = 0;
    
            foreach ($records as $record) {
                try {
                    if (!$this->entityManager->isOpen()) {
                        $this->resetEntityManager();
                    }
    
                    $transformedRecord = $this->transformCsvRecord($record, $fileMapping);
                    $this->processRecord($transformedRecord, $broker, $fileMapping);
    
                    if (++$i % $batchSize === 0) {
                        $this->entityManager->flush();
                        $this->entityManager->clear();
                    }
                } catch (\Throwable $e) {
                    $this->logger->error("Skipping record due to error: " . $e->getMessage());
                    $this->entityManager->clear(); // Prevent partial persistence issues
                }
            }
    
            if ($this->entityManager->isOpen()) {
                $this->entityManager->flush();
            }
    
            $io->success("Finished processing " . basename($filePath));
        } catch (\Throwable $e) {
            $this->logger->error("Failed processing {$filePath}: " . $e->getMessage());
            $io->error("Error: " . $e->getMessage());
        }
    }
    
    private function transformCsvRecord(array $record, array $fileMapping): array
    {
        $transformedRecord = [];
    
        foreach ($fileMapping as $configKey => $csvHeader) {
            if (isset($record[$csvHeader])) {
                $transformedRecord[$configKey] = $record[$csvHeader];
            } else {
                $this->logger->warning("CSV is missing expected column: '$csvHeader'");
            }
        }
    
        return $transformedRecord;
    }

    private function processRecord(array $record, Broker $broker, array $fileMapping): void
    {
        $policyNumber = trim($record['PolicyNumber'] ?? '');
        $clientRef = trim($record['ClientRef'] ?? '');

        if (!$policyNumber || !$clientRef) {
            $this->logger->warning("Skipping record due to missing fields: " . json_encode($record));
            return;
        }

        $client = $this->findOrCreateClient($clientRef, trim($record['ClientType'] ?? ''), $broker);
        $insurer = $this->findOrCreateEntity(Insurer::class, trim($record['Insurer'] ?? ''), $broker);
        $product = $this->findOrCreateEntity(Product::class, trim($record['Product'] ?? ''), $broker);
        $event = $this->findOrCreateEntity(Event::class, trim($record['BusinessEvent'] ?? ''), $broker);

        $policyRepo = $this->entityManager->getRepository(Policy::class);
        if ($policyRepo->findOneBy(['policy_number' => $policyNumber, 'broker' => $broker])) {
            return;
        }

        // Create and persist Policy
        $policy = new Policy();
        $policy->setPolicyNumber($policyNumber)
            ->setInsurerPolicyNumber(trim($record['InsurerPolicyNumber'] ?? ''))
            ->setRootPolicyRef(trim($record['RootPolicyRef'] ?? ''))
            ->setPolicyType(trim($record['PolicyType'] ?? ''))
            ->setStartDate($this->parseDate($record['StartDate'] ?? '', true))
            ->setEndDate($this->parseDate($record['EndDate'] ?? ''))
            ->setEffectiveDate($this->parseDate($record['EffectiveDate'] ?? ''))
            ->setRenewalDate($this->parseDate($record['RenewalDate'] ?? '', true))
            ->setBusinessDescription(trim($record['BusinessDescription'] ?? ''))
            ->setClient($client)
            ->setInsurer($insurer)
            ->setBroker($broker)
            ->setProduct($product)
            ->setEvent($event);

        $this->entityManager->persist($policy);

        // Create and persist Financials
        $financials = new Financials();
        $financials->setPolicy($policy)
            ->setBroker($broker)
            ->setInsuredAmount((float) ($record['InsuredAmount'] ?? 0))
            ->setPremium((float) ($record['Premium'] ?? 0))
            ->setCommission((float) ($record['Commission'] ?? 0))
            ->setAdminFee((float) ($record['AdminFee'] ?? 0))
            ->setIptaAmount((float) ($record['IPTAmount'] ?? 0))
            ->setPolicyFee((float) ($record['PolicyFee'] ?? 0));

        $this->entityManager->persist($financials);
    }

    private function resetEntityManager(): void
    {
        if (!$this->entityManager->isOpen()) {
            $this->logger->warning("Resetting the EntityManager...");
            $this->entityManager = $this->managerRegistry->getManager();
        }
    }

    private function findOrCreateClient(string $clientRef, string $clientType, Broker $broker): Client
    {
        if (!$this->entityManager->isOpen()) {
            $this->logger->error("EntityManager was closed. Resetting...");
            $this->resetEntityManager();
        }

        $repository = $this->entityManager->getRepository(Client::class);
    
        // Check if client already exists before inserting
        $client = $repository->findOneBy([
            'client_ref' => $clientRef,
            'broker' => $broker
        ]);

        if ($client) {
            return $client;
        }

        try {
            $this->entityManager->beginTransaction();

            $client = new Client();
            $client->setClientRef($clientRef)
                   ->setBroker($broker)
                   ->setClientType($clientType);

            $this->entityManager->persist($client);
            $this->entityManager->flush();
            
            $this->entityManager->commit();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            $this->entityManager->rollback();
            $this->entityManager->clear();
        
            $this->logger->error("Duplicate client detected: " . $e->getMessage());
            $client = $repository->findOneBy([
                'client_ref' => $clientRef,
                'broker' => $broker
            ]);

            if (!$client) {
                throw new \Exception("Critical Error: Client creation failed for reference '$clientRef' due to duplicate entry.");
            }
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->entityManager->clear();

            $this->logger->error("Failed to persist client: " . $e->getMessage());
            throw new \Exception("Critical Error: Client creation failed for reference '$clientRef'.");
        }

        return $client;
    }

    private function findOrCreateEntity(string $entityClass, string $name, ?Broker $broker = null)
    {
        if (!$name) {
            return null;
        }

        $criteria = ['name' => $name];

        // If the entity has a broker field, include it in the lookup
        if ($broker && property_exists($entityClass, 'broker')) {
            $criteria['broker'] = $broker;
        }

        $repository = $this->entityManager->getRepository($entityClass);
        $entity = $repository->findOneBy($criteria);

        if (!$entity) {
            try {
                $entity = new $entityClass();
                $entity->setName($name);

                if ($broker && property_exists($entityClass, 'broker')) {
                    $entity->setBroker($broker);
                }

                $this->entityManager->persist($entity);
                // $this->entityManager->flush();
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                // Retrieve the existing entity if another process inserted it first
                $this->entityManager->clear();
                $entity = $repository->findOneBy($criteria);
            }
        }

        return $entity;
    }

    private function validateMapping(array $fileMapping, array $csvHeaders): bool
    {
        foreach ($fileMapping as $configKey => $csvHeader) {
            if (!in_array($csvHeader, $csvHeaders, true)) {
                $this->logger->error("CSV file is missing expected column: '$csvHeader'");
                return false;
            }
        }
        return true;
    }

    private function parseDate(string $dateString, bool $allowNull = false): ?\DateTimeInterface
    {
        if ((!$dateString && $allowNull) || ($dateString === 'Not Known')) {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'm/d/Y'] as $format) {
            if ($date = \DateTime::createFromFormat($format, $dateString)) {
                return $date;
            }
        }

        throw new \Exception("Invalid date format: $dateString");
    }
}
