<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Broker;
use App\Entity\Policy;
use App\Entity\Insurer;
use App\Entity\Product;
use App\Entity\Financials;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class RecordProcessingService
{
    private EntityManagerInterface $entityManager;
    private FileEntityService $fileEntityService;
    private ManagerRegistry $managerRegistry;

    public function __construct(
        EntityManagerInterface $entityManager,
        FileEntityService $fileEntityService,
        ManagerRegistry $managerRegistry,
    ) {
        $this->entityManager = $entityManager;
        $this->fileEntityService = $fileEntityService;
        $this->managerRegistry = $managerRegistry;
    }

    public function processRecords(iterable $records, array $fileMapping, Broker $broker): void
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

    private function transformCsvRecord(array $record, array $fileMapping): array
    {
        $transformedRecord = [];
        foreach ($fileMapping as $configKey => $csvHeader) {
            if (isset($record[$csvHeader])) {
                $transformedRecord[$configKey] = $record[$csvHeader];
            }
        }
        return $transformedRecord;
    }

    private function processRecord(array $record, Broker $broker): void
    {
        $policyNumber = trim($record['PolicyNumber'] ?? '');
        $clientRef = trim($record['ClientRef'] ?? '');

        if (!$policyNumber || !$clientRef) {
            return;
        }

        $client = $this->fileEntityService->findOrCreateBrokerClient($clientRef, trim($record['ClientType'] ?? ''), $broker);
        $insurer = $this->fileEntityService->findOrCreateEntity(Insurer::class, trim($record['Insurer'] ?? ''), $broker);
        $product = $this->fileEntityService->findOrCreateEntity(Product::class, trim($record['Product'] ?? ''), $broker);
        $event = $this->fileEntityService->findOrCreateEntity(Event::class, trim($record['BusinessEvent'] ?? ''), $broker);

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

    private function flushAndClear(): void
    {
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function ensureBrokerIsManaged(Broker $broker): Broker
    {
        return $this->entityManager->find(Broker::class, $broker->getId()) ?? $broker;
    }

    private function resetEntityManager(): void
    {
        if (!$this->entityManager->isOpen()) {
            $this->entityManager = $this->managerRegistry->getManager();
        }
    }

    private function handleRecordError(\Throwable $e): void
    {
        $this->entityManager->clear(); // Prevent partial persistence issues
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
