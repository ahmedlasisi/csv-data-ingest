<?php

namespace App\Service;

use App\Entity\Broker;
use App\Repository\BrokerRepository;
use App\Repository\PolicyRepository;
use Symfony\Component\Cache\Attribute\Cache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\CacheRefreshMessage;

class AggregationService
{
    private const CACHE_TTL_SUMMARY = 900; // 15 minutes
    private const CACHE_TTL_BROKER = 600;   // 10 minutes

    public function __construct(
        private  BrokerRepository $brokerRepository,
        private  PolicyRepository $policyRepository,
        private  CacheHelper $cacheHelper,
        private MessageBusInterface $bus
    ) {
        $this->brokerRepository = $brokerRepository;
        $this->policyRepository = $policyRepository;
        $this->cacheHelper = $cacheHelper;
        $this->bus = $bus;
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

    /**
    * Triggers broker cache refresh in the background.
    */
    public function triggerBrokerCacheRefresh(Broker $broker): void
    {
        $this->bus->dispatch(new CacheRefreshMessage('aggregation_summary'));
        $this->bus->dispatch(new CacheRefreshMessage('aggregation_by_broker'));
        $this->bus->dispatch(new CacheRefreshMessage('aggregation_broker_' . $broker->getUuid()));
    }
    /**
    * Triggers cache refresh in the background.
    */
    public function triggerCacheRefresh(): void
    {
        $this->bus->dispatch(new CacheRefreshMessage('aggregation_summary'));
        $this->bus->dispatch(new CacheRefreshMessage('aggregation_by_broker'));

        $brokers = $this->brokerRepository->findAll();

        foreach ($brokers as $broker) {
            $this->bus->dispatch(new CacheRefreshMessage('aggregation_broker_' . $broker->getUuid()));
        }
    }
}
