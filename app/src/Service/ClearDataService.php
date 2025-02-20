<?php

namespace App\Service;

use App\Entity\Broker;
use App\Service\CacheHelper;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

class ClearDataService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private CacheHelper $cacheHelper;
    private AggregationService $aggregationService;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        CacheHelper $cacheHelper,
        AggregationService $aggregationService
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->cacheHelper = $cacheHelper;
        $this->aggregationService = $aggregationService;
    }

    public function clearBrokerData(Broker $broker): void
    {
        $this->entityManager->beginTransaction();

        try {
            $this->deleteEntities($broker, [
                'App\Entity\Financials' => 'broker',
                'App\Entity\Policy' => 'broker',
                'App\Entity\BrokerClient' => 'broker',
                'App\Entity\Insurer' => 'broker',
                'App\Entity\Product' => 'broker',
                'App\Entity\Event' => 'broker'
            ]);

            $this->entityManager->flush();
            $this->entityManager->commit();
            $this->aggregationService->triggerBrokerCacheRefresh($broker);

            $this->logger->info("Cleared all policy-related data for broker: " . $broker->getId());
        } catch (\Throwable $e) {
            $this->entityManager->rollBack();
            $this->logger->error("Failed to clear broker data: " . $e->getMessage());
            throw new \Exception("Error clearing broker data: " . $e->getMessage());
        }
    }

    public function clearBrokerPoliciesData(Broker $broker): void
    {
        $this->entityManager->beginTransaction();

        try {
            $this->deleteEntities($broker, [
                'App\Entity\Financials' => 'broker',
                'App\Entity\Policy' => 'broker'
            ]);

            $this->cacheHelper->invalidate("aggregation_summary");
            $this->cacheHelper->invalidate("aggregation_by_broker_{$broker->getUuid()}");

            $this->entityManager->commit();
            $this->aggregationService->triggerBrokerCacheRefresh($broker);
            $this->logger->info("Cleared policies data for broker: " . $broker->getName());
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error("Failed to clear broker policies: " . $e->getMessage());
            throw $e;
        }
    }

    public function clearBrokerAllData(Broker $broker): void
    {
        $this->entityManager->beginTransaction();

        try {
            $this->clearBrokerPoliciesData($broker);

            $this->deleteEntities($broker, [
                'App\Entity\Client' => 'broker',
                'App\Entity\BrokerConfig' => 'broker'
            ]);

            $this->cacheHelper->invalidate("aggregation_summary");
            $this->cacheHelper->invalidate("aggregation_by_broker_{$broker->getUuid()}");

            $this->entityManager->commit();
            $this->logger->info("Cleared all data for broker: " . $broker->getName());
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error("Failed to clear broker data: " . $e->getMessage());
            throw $e;
        }
    }

    private function deleteEntities(Broker $broker, array $entities): void
    {
        foreach ($entities as $entity => $field) {
            $queryStr = "DELETE FROM $entity e WHERE e.$field = :broker";
            $query = $this->entityManager->createQuery($queryStr);
            $query->setParameter('broker', $broker->getId());
            $query->execute();
        }
    }
}
