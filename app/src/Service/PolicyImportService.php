<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Broker;
use App\Entity\Policy;
use League\Csv\Reader;
use App\Entity\Insurer;
use App\Entity\Product;
use App\Entity\Financials;
use App\Entity\BrokerClient;
use App\Entity\BrokerConfig;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Interface\PolicyImportLoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PolicyImportService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private string $dataDirectory;
    private PolicyImportLoggerInterface $importLogger;
    private UrlGeneratorInterface $urlGenerator;

    private ManagerRegistry $managerRegistry;

    /**
    * In–memory caches for performance.
    *
    * @var array<string, array<string, BrokerClient>>
    */
    private array $clientCache = [];

    /**
     * Generic entity caches keyed by entity class, broker id and lookup key.
     *
     * @var array<string, array<string, object>>
     */
    private array $entityCache = [];
    private bool $useCache = true;
    private AggregationService $aggregationService;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        PolicyImportLoggerInterface $importLogger,
        ManagerRegistry $managerRegistry,
        UrlGeneratorInterface $urlGenerator,
        AggregationService $aggregationService
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->importLogger = $importLogger;
        $this->managerRegistry = $managerRegistry;
        $this->dataDirectory = __DIR__ . '/../../var/data';
        $this->urlGenerator = $urlGenerator;
        $this->aggregationService = $aggregationService;
    }

    public function setLogger(PolicyImportLoggerInterface $importLogger): void
    {
        $this->importLogger = $importLogger;
    }

    public function importPolicies(): void
    {
        $brokerConfigs = $this->entityManager->getRepository(BrokerConfig::class)->findAll();

        if (empty($brokerConfigs)) {
            $this->importLogger->warning("No broker configurations found in database.");
            return;
        }

        foreach ($brokerConfigs as $config) {
            $this->processBrokerConfig($config);
        }
    }

    private function processBrokerConfig(BrokerConfig $config): void
    {
        $filePath = realpath($this->dataDirectory . '/' . $config->getFileName());
      
        $broker = $config->getBroker();

        if (!file_exists($filePath)) {
            $this->importLogger->warning("File not found: $filePath");
            return;
        }

        $this->processFile($filePath, $broker);
    }

    public function handleFileUpload(UploadedFile $file, Broker $broker): JsonResponse
    {
        $filePath = $file->getPathname(); // Temporary location of the uploaded file
        $result = $this->processFile($filePath, $broker);

        if (empty($result)) {
            // return new JsonResponse(['status' => 'success', 'message' => "File processed successfully"]);
            return new JsonResponse([
                'redirect' => $this->generateUrl('broker_config_index', ['format' => 'admin']),
                'status' => 'success',
                'message' => 'CSV file uploaded successfully.'
            ]);
        }

        return new JsonResponse(['status' => 'error', 'message' => $result]);
    }

    private function generateUrl(string $route, array $parameters = []): string
    {
        return $this->urlGenerator->generate($route, $parameters);
    }

    private function processFile(string $filePath, Broker $broker): ?array
    {
        $this->importLogger->info("Processing file: " . basename($filePath));

        $config = $broker->getConfig();
        $errors = [];

        $this->logger->info("Starting to process file: $filePath");

        if (!file_exists($filePath)) {
            $this->logger->error("File does not exist: $filePath");
            $this->importLogger->error("File does not exist: " . basename($filePath));

            return  ["File does not exist: $filePath"];
        }

        try {
            $this->logger->info("Reading CSV file: $filePath");
            $csv = $this->readCsvFile($filePath);
            $csvHeaders = $csv->getHeader();
            $this->logger->info("CSV headers: " . implode(', ', $csvHeaders));
            $fileMapping = $config->getFileMapping();
            $this->logger->info("File mapping: " . json_encode($fileMapping));

            if (!$this->validateMapping($fileMapping, $csvHeaders)) {
                throw new \Exception("Invalid JSON mapping for file: " . $config->getFileName());
            }

            $this->logger->info("Valid JSON mapping for file: " . $config->getFileName());
            $this->logger->info("Starting to process records for file: $filePath");
            $this->processRecords($csv->getRecords(), $fileMapping, $broker);
            
            $this->logger->info("Finished processing records for file: $filePath");
        } catch (\Throwable $e) {
            $this->importLogger->error("Exception caught during file processing: " . $e->getMessage());
            $this->logAndReturnProcessingError($e, $filePath);
        }
        $this->aggregationService->triggerCacheRefresh();

        $this->importLogger->success("Finished processing " . basename($filePath));

        return $errors;
    }
    
    private function readCsvFile(string $filePath): Reader
    {
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);
        return $csv;
    }

    private function processRecords(iterable $records, array $fileMapping, Broker $broker): void
    {
        $batchSize = 50;
        $i = 0;

        // Ensure Broker is managed before processing
        $broker = $this->ensureBrokerIsManaged($broker);

        foreach ($records as $record) {
            try {
                // If EntityManager was closed, reset it and re-fetch Broker
                if (!$this->entityManager->isOpen()) {
                    $this->resetEntityManager();
                    $broker = $this->ensureBrokerIsManaged($broker);
                }

                $transformedRecord = $this->transformCsvRecord($record, $fileMapping);
                $this->processRecord($transformedRecord, $broker);

                // Flush and clear batch processing
                if (++$i % $batchSize === 0) {
                    $this->flushAndClear();
                    $broker = $this->ensureBrokerIsManaged($broker); // Re-fetch Broker after clearing
                }
            } catch (\Throwable $e) {
                $this->handleRecordError($e);
            }
        }

        // Final flush after processing all records
        $this->flushAndClear();
    }

    // Helper method to flush, clear, and clean up caches
    private function flushAndClear(): void
    {
        $this->entityManager->flush();
        $this->entityManager->clear();
        $this->clearCaches();
    }

    // Helper method to re-fetch Broker from the database
    private function ensureBrokerIsManaged(Broker $broker): Broker
    {
        return $this->entityManager->find(Broker::class, $broker->getId()) ?? $broker;
    }

    private function handleRecordError(\Throwable $e): void
    {
        $this->logger->error("Skipping record due to error: " . $e->getMessage());
        $this->entityManager->clear(); // Prevent partial persistence issues
        $this->clearCaches();
    }

    private function logAndReturnProcessingError(\Throwable $e, string $filePath): array
    {
        $this->logger->error("Failed processing {$filePath}: " . $e->getMessage());
        $this->importLogger->error("Error: " . $e->getMessage());
        return ["Error" => $e->getMessage()];
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

    private function processRecord(array $record, Broker $broker): void
    {
        $policyNumber = trim($record['PolicyNumber'] ?? '');
        $clientRef = trim($record['ClientRef'] ?? '');

        if (!$policyNumber || !$clientRef) {
            $this->logger->warning("Skipping record due to missing fields: " . json_encode($record));
            return;
        }

        $client = $this->findOrCreateBrokerClient($clientRef, trim($record['ClientType'] ?? ''), $broker);
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
            ->setBrokerClient($client)
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

    private function findOrCreateBrokerClient(string $clientRef, string $clientType, Broker $broker): BrokerClient
    {
        $cacheKey = $broker->getId() . '-' . $clientRef;
        if ($this->useCache && isset($this->clientCache[$cacheKey])) {
            return $this->clientCache[$cacheKey];
        }
        
        if (!$this->entityManager->isOpen()) {
            $this->logger->error("EntityManager was closed. Resetting...");
            $this->resetEntityManager();
        }
        
        $repository = $this->entityManager->getRepository(BrokerClient::class);
    
        // Check if client already exists before inserting
        $client = $repository->findOneBy([
            'client_ref' => $clientRef,
            'broker' => $broker
        ]);

        if ($client) {
            if ($this->useCache) {
                $this->clientCache[$cacheKey] = $client;
            }
            return $client;
        }

        try {
            $this->entityManager->beginTransaction();

            $client = new BrokerClient();
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
                throw new \Exception("Critical Error: Broker Client creation failed for reference '$clientRef' due to duplicate entry.");
            }
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->entityManager->clear();

            $this->logger->error("Failed to persist client: " . $e->getMessage());
            throw new \Exception("Critical Error: Broker Client creation failed for reference '$clientRef'.");
        }

        if ($this->useCache) {
            $this->clientCache[$cacheKey] = $client;
        }
        return $client;
    }

    public function findOrCreateEntity(string $entityClass, string $name, ?Broker $broker = null)
    {
        if (!$name) {
            return null;
        }

        $brokerKey = $broker ? $broker->getId() : 'none';
        $cacheKey  = $brokerKey . '-' . $name;
        if ($this->useCache && !isset($this->entityCache[$entityClass])) {
            $this->entityCache[$entityClass] = [];
        }
        if ($this->useCache && isset($this->entityCache[$entityClass][$cacheKey])) {
            return $this->entityCache[$entityClass][$cacheKey];
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

        $this->entityCache[$entityClass][$cacheKey] = $entity;
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

    public function validateConfigMapping(array $mapping): bool
    {
        $requiredKeys = [
            "PolicyNumber", "InsuredAmount", "StartDate", "EndDate", "AdminFee",
            "BusinessDescription", "BusinessEvent", "ClientType", "ClientRef",
            "Commission", "EffectiveDate", "InsurerPolicyNumber", "IPTAmount",
            "Premium", "PolicyFee", "PolicyType", "Insurer", "RenewalDate",
            "RootPolicyRef", "Product"
        ];

        // Check if all required keys are present in the mapping
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $mapping) || !is_string($mapping[$key]) || empty(trim($mapping[$key]))) {
                return false; // If missing or not a valid string, return false
            }
        }

        return true; // Mapping is valid
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

    /**
     * Clear in–memory caches.
     */
    public function clearCaches(): void
    {
        $this->clientCache = [];
        $this->entityCache = [];
    }

    public function setUseCache(bool $useCache): void
    {
        $this->useCache = $useCache;
    }
}
