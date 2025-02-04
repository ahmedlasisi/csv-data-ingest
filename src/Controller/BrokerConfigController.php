<?php

namespace App\Controller;

use App\Entity\Broker;
use App\Entity\BrokerConfig;
use App\Service\PolicyImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\Uid\Uuid;

#[Route('/api/brokers')]
#[IsGranted('ROLE_ADMIN')]
class BrokerConfigController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private PolicyImportService $policyImportService;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        PolicyImportService $policyImportService,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->policyImportService = $policyImportService;
        $this->validator = $validator;
    }

    /**
     * Get all broker configurations.
     */
    #[Route('/config', methods: ['GET'])]
    public function getAllBrokerConfigs(): JsonResponse
    {
        $configs = $this->entityManager->getRepository(BrokerConfig::class)->findAll();

        return $this->json(array_map(fn (BrokerConfig $config) => [
            'uuid' => $config->getBroker()->getUuid(),
            'broker_name' => $config->getBroker()?->getName(),
            'file_name' => $config->getFileName(),
            'file_mapping' => $config->getFileMapping(),
        ], $configs));
    }

    /**
     * Create a new broker configuration.
     */
    #[Route('/config', methods: ['POST'])]
    public function createBrokerConfig(Request $request): JsonResponse
    {
        $data = $this->getJsonRequestBody($request, ['broker_name', 'file_name', 'file_mapping']);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        // Validate file mapping format
        if (!$this->policyImportService->validateConfigMapping($data['file_mapping'])) {
            return $this->json(['error' => 'Invalid JSON Config mapping for file'], JsonResponse::HTTP_CONFLICT);
        }

        // Fetch or create broker
        $broker = $this->entityManager->getRepository(Broker::class)->findOneBy(['name' => trim($data['broker_name'])]);
        if (!$broker) {
            $broker = $this->policyImportService->findOrCreateEntity(Broker::class, trim($data['broker_name']), $broker);
        }

        // Prevent duplicate broker config
        if ($this->entityManager->getRepository(BrokerConfig::class)->findOneBy(['broker' => $broker])) {
            return $this->json(['error' => 'Broker config already exists'], JsonResponse::HTTP_CONFLICT);
        }

        // Create BrokerConfig
        $config = new BrokerConfig();
        $config->setBroker($broker);
        $config->setFileName($data['file_name']);
        $config->setFileMapping($data['file_mapping']);

        return $this->persistAndReturnResponse($config, 'Broker config created successfully', JsonResponse::HTTP_CREATED);
    }

    /**
     * Update an existing broker configuration using UUID.
     */
    #[Route('/config/{uuid}', methods: ['PUT'])]
    public function updateBrokerConfig(string $uuid, Request $request): JsonResponse
    {
        $config = $this->findBrokerConfigByUuid($uuid);
        if ($config instanceof JsonResponse) {
            return $config;
        }

        $data = $this->getJsonRequestBody($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        // Update file_name
        if (!empty($data['file_name'])) {
            $config->setFileName($data['file_name']);
        }

        // Update & Validate file_mapping
        if (!empty($data['file_mapping'])) {
            if (!$this->policyImportService->validateConfigMapping($data['file_mapping'])) {
                return $this->json(['error' => 'Invalid JSON Config mapping for file'], JsonResponse::HTTP_CONFLICT);
            }
            $config->setFileMapping($data['file_mapping']);
        }

        return $this->persistAndReturnResponse($config, 'Broker config updated successfully');
    }

    /**
     * Delete a broker configuration using UUID.
     */
    #[Route('/config/{uuid}', methods: ['DELETE'])]
    public function deleteBrokerConfig(string $uuid): JsonResponse
    {
        // dd('here');
        $broker = $this->findBrokerByUuid($uuid);
        if ($broker instanceof JsonResponse) {
            return $broker;
        }

        // Prevent deletion of critical configurations
        // if (method_exists($config, 'isCritical') && $config->isCritical()) {
        //     return $this->json(['error' => 'Cannot delete a critical broker configuration'], JsonResponse::HTTP_FORBIDDEN);
        // }

        $this->entityManager->remove($broker);
        $this->entityManager->flush();

        return $this->json(['message' => 'Broker config deleted successfully'], JsonResponse::HTTP_OK);
    }

    /**
     * Find BrokerConfig by UUID with validation.
     */
    private function findBrokerByUuid(string $uuid): Broker|JsonResponse
    {
        if (!Uuid::isValid($uuid)) {
            return $this->json(['error' => 'Invalid UUID format'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $config = $this->entityManager->getRepository(Broker::class)->findOneBy(['uuid' => $uuid]);

        return $config ?: $this->json(['error' => 'Broker details not found'], JsonResponse::HTTP_NOT_FOUND);
    }

    /**
     * Find BrokerConfig by UUID with validation.
     */
    private function findBrokerConfigByUuid(string $uuid): BrokerConfig|JsonResponse
    {
        if (!Uuid::isValid($uuid)) {
            return $this->json(['error' => 'Invalid UUID format'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $config = $this->entityManager->getRepository(Broker::class)->findOneBy(['uuid' => $uuid])->getConfig();

        return $config ?: $this->json(['error' => 'Broker configuration not found'], JsonResponse::HTTP_NOT_FOUND);
    }

    /**
     * Get JSON request body with required field validation.
     */
    private function getJsonRequestBody(Request $request, array $requiredFields = []): array|JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON request body'], JsonResponse::HTTP_BAD_REQUEST);
        }

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->json(['error' => "Missing required field: $field"], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        return $data;
    }

    /**
     * Persist entity & return JSON response.
     */
    private function persistAndReturnResponse($entity, string $message, int $status = JsonResponse::HTTP_OK): JsonResponse
    {
        $errors = $this->validator->validate($entity);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $this->json(['message' => $message], $status);
    }
}
