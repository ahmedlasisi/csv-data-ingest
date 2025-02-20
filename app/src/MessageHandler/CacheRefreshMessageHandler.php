<?php

namespace App\MessageHandler;

use App\Service\CacheHelper;
use App\Service\AggregationService;
use App\Message\CacheRefreshMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CacheRefreshMessageHandler
{
    private CacheHelper $cacheHelper;
    private AggregationService $aggregationService;

    public function __construct(CacheHelper $cacheHelper, AggregationService $aggregationService)
    {
        $this->cacheHelper = $cacheHelper;
        $this->aggregationService = $aggregationService;
    }

    public function __invoke(CacheRefreshMessage $message)
    {
        $cacheKey = $message->getCacheKey();

        switch ($cacheKey) {
            case 'aggregation_summary':
                $this->cacheHelper->set($cacheKey, $this->aggregationService->getAggregatedDataSummary(), 1800);
                break;
            case 'aggregation_by_broker':
                $this->cacheHelper->set($cacheKey, $this->aggregationService->getAggregatedBrokersData(), 1800);
                break;
            default:
                if (str_starts_with($cacheKey, 'aggregation_broker_')) {
                    $uuid = str_replace('aggregation_broker_', '', $cacheKey);
                    $broker = $this->aggregationService->getBrokerByUuid($uuid);
                    $this->cacheHelper->set($cacheKey, $this->aggregationService->getAggregationDataByBroker($broker), 900);
                }
                break;
        }
    }
}
