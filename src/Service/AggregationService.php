<?php

namespace App\Service;

use App\Entity\Broker;
use App\Repository\BrokerRepository;
use App\Repository\PolicyRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AggregationService
{
    private const CACHE_TTL_SUMMARY = 1800; // 30 minutes
    private const CACHE_TTL_BROKER = 900;   // 15 minutes

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
        });
    }

    public function getAggregatedDataSummary(): array
    {
        return $this->cacheHelper->get(
            'aggregation_summary',
            fn () => $this->policyRepository->findDataSummary(),
            self::CACHE_TTL_SUMMARY
        );
    }

    public function getAggregatedBrokersData(): array
    {
        return $this->cacheHelper->get(
            'aggregation_by_broker',
            fn () => $this->policyRepository->findBrokerAggregation(),
            self::CACHE_TTL_SUMMARY
        );
    }

    public function getAggregationDataByBroker(Broker $broker): array
    {
        $cacheKey = 'aggregation_broker_' . $broker->getUuid();

        return $this->cacheHelper->get(
            $cacheKey,
            fn () => $this->policyRepository->findByBroker($broker),
            self::CACHE_TTL_BROKER
        );
    }
}
