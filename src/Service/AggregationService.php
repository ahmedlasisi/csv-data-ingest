<?php

namespace App\Service;

use App\Entity\Broker;
use App\Repository\BrokerRepository;
use App\Repository\PolicyRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AggregationService
{
    public function __construct(
        private readonly BrokerRepository $brokerRepository,
        private readonly PolicyRepository $policyRepository,
        private readonly CacheHelper $cacheHelper
    ) {
    }

    public function getBrokerByUuid(string $uuid): Broker
    {
        return $this->cacheHelper->get("broker_{$uuid}", function () use ($uuid) {
            $broker = $this->brokerRepository->findOneBy(['uuid' => $uuid]);

            if (!$broker) {
                throw new NotFoundHttpException("Broker with UUID {$uuid} not found.");
            }

            return $broker;
        }, PolicyRepository::CACHE_TTL_BROKER);
    }

    public function getAggregatedDataSummary(): array
    {
        return $this->policyRepository->findDataSummary();
    }

    public function getAggregatedBrokersData(): array
    {
        return $this->policyRepository->findBrokerAggregation();
    }

    public function getAggregationDataByBroker(Broker $broker): array
    {
        return $this->policyRepository->findByBroker($broker);
    }
}
