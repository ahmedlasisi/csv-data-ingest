<?php

namespace App\Controller;

use App\Service\CacheHelper;
use App\Service\AggregationService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/cache')]
#[IsGranted('ROLE_ADMIN')] // Restrict access to admin users
class CacheController extends AbstractController
{
    private CacheHelper $cacheHelper;
    private AggregationService $aggregationService;

    public function __construct(CacheHelper $cacheHelper, AggregationService $aggregationService)
    {
        $this->cacheHelper = $cacheHelper;
        $this->aggregationService = $aggregationService;
    }

    /**
     * Clears all cache.
     */
    #[Route('', methods: ['DELETE'])]
    public function refreshCache(): JsonResponse
    {
        $this->aggregationService->triggerCacheRefresh();

        return $this->json(['message' => 'Cache refresh triggered successfully']);
    }

    /**
     * Clears a specific cache key.
     */
    #[Route('/{key}', methods: ['DELETE'])]
    public function clearCacheByKey(string $key): JsonResponse
    {
        $this->cacheHelper->invalidate($key);
        return $this->json(['message' => "Cache for key '{$key}' cleared"], JsonResponse::HTTP_OK);
    }
}
