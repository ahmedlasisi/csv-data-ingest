<?php

namespace App\Controller;

use App\Service\CacheHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/cache')]
#[IsGranted('ROLE_ADMIN')] // Restrict access to admin users
class CacheController extends AbstractController
{
    private CacheHelper $cacheHelper;

    public function __construct(CacheHelper $cacheHelper)
    {
        $this->cacheHelper = $cacheHelper;
    }

    /**
     * Clears all cache.
     */
    #[Route('', methods: ['DELETE'])]
    public function clearAllCache(): JsonResponse
    {
        $this->cacheHelper->clearAllCache();
        return $this->json(['message' => 'All cache cleared successfully'], JsonResponse::HTTP_OK);
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
