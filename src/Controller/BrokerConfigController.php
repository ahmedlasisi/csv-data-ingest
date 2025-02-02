<?php

namespace App\Controller;

use App\Entity\Broker;
use App\Entity\BrokerConfig;
use App\Service\PolicyImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Route('/api/brokers')]
class BrokerConfigController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private PolicyImportService $policyImportService;

    public function __construct(
        EntityManagerInterface $entityManager,
        PolicyImportService $policyImportService,
    ) {
        $this->entityManager = $entityManager;
        $this->policyImportService = $policyImportService;
    }

    /**
     * Get all broker configurations.
     */
    #[Route('/config', methods: ['GET'])]
    public function getAllBrokerConfigs(): JsonResponse
    {
        $configs = $this->entityManager->getRepository(BrokerConfig::class)->findAll();
        $data = [];

        foreach ($configs as $config) {
            $data[] = [
                'uuid' => $config->getUuid(),
                'broker_name' => $config->getBroker()->getName(),
                'file_name' => $config->getFileName(),
                'file_mapping' => $config->getFileMapping(),
            ];
        }

        return $this->json($data);
    }

    /**
     * Create a new broker configuration.
     */
    #[Route('/config', methods: ['POST'])]
    public function createBrokerConfig(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['broker_name'], $data['file_name'], $data['file_mapping'])) {
            return $this->json(['error' => 'Missing required fields'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Find or create broker
        $broker = $this->entityManager->getRepository(Broker::class)->findOneBy(['name' => $data['broker_name']]);
        if (!$broker) {
            $broker =  $this->policyImportService->findOrCreateEntity(Broker::class, trim($data['broker_name'] ?? ''), $broker);
        }

        if (!$this->policyImportService->validateConfigMapping($data['file_mapping'])) {
            return $this->json(['error' => 'Invalid JSON Config mapping for file'], JsonResponse::HTTP_CONFLICT);
        }

        // Check if a config already exists for this broker
        $existingConfig = $this->entityManager->getRepository(BrokerConfig::class)->findOneBy(['broker' => $broker]);
        if ($existingConfig) {
            return $this->json(['error' => 'Broker config already exists'], JsonResponse::HTTP_CONFLICT);
        }

        // Create new BrokerConfig
        $config = new BrokerConfig();
        $config->setBroker($broker);
        $config->setFileName($data['file_name']);
        $config->setFileMapping($data['file_mapping']);

        $this->entityManager->persist($config);
        $this->entityManager->flush();

        return $this->json(['message' => 'Broker config created successfully'], JsonResponse::HTTP_CREATED);
    }

    /**
     * Update an existing broker configuration.
     */
    #[Route('/config/{id}', methods: ['PUT'])]
    public function updateBrokerConfig(int $id, Request $request): JsonResponse
    {
        $config = $this->entityManager->getRepository(BrokerConfig::class)->find($id);

        if (!$config) {
            throw new NotFoundHttpException('Broker configuration not found.');
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['file_name'])) {
            $config->setFileName($data['file_name']);
        }

        if (isset($data['file_mapping'])) {
            $config->setFileMapping($data['file_mapping']);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Broker config updated successfully']);
    }

    /**
     * Delete a broker configuration.
     */
    #[Route('/config/{id}', methods: ['DELETE'])]
    public function deleteBrokerConfig(int $id): JsonResponse
    {
        $config = $this->entityManager->getRepository(BrokerConfig::class)->find($id);

        if (!$config) {
            throw new NotFoundHttpException('Broker configuration not found.');
        }

        $this->entityManager->remove($config);
        $this->entityManager->flush();

        return $this->json(['message' => 'Broker config deleted successfully']);
    }
}
