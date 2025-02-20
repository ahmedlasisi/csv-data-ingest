<?php

namespace App\Service;

use App\Entity\Broker;
use App\Repository\BrokerRepository;
use App\Repository\PolicyRepository;
use Symfony\Component\Cache\Attribute\Cache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AggregationService
{
    private const CACHE_TTL_SUMMARY = 900; // 15 minutes
    private const CACHE_TTL_BROKER = 600;   // 10 minutes

    public function __construct(
        private readonly BrokerRepository $brokerRepository,
        private readonly PolicyRepository $policyRepository,
        private readonly CacheHelper $cacheHelper
    ) {
    }

    public function getBrokerByUuid(string $uuid): Broker
    {
        $broker = $this->brokerRepository->findOneBy(['uuid' => $uuid]);

        if (!$broker) {
            throw new NotFoundHttpException("Broker with UUID {$uuid} not found.");
        }

        return $broker;
    }

    #[Cache(expiresAfter: CACHE_TTL_SUMMARY, key: 'aggregation_summary')]
    public function getAggregatedDataSummary(): array
    {
        return $this->policyRepository->findDataSummary();
    }

    #[Cache(expiresAfter: CACHE_TTL_SUMMARY, key: 'aggregation_by_broker')]
    public function getAggregatedBrokersData(): array
    {
        return $this->policyRepository->findBrokerAggregation();
    }

    #[Cache(expiresAfter: CACHE_TTL_BROKER, key: 'aggregation_broker_{broker.getUuid()}')]
    public function getAggregationDataByBroker(Broker $broker): array
    {
        return $this->policyRepository->findByBroker($broker);
    }
}
