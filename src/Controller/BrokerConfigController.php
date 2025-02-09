<?php

namespace App\Controller;

use App\Entity\Broker;
use App\Entity\BrokerConfig;
use App\Service\CacheHelper;
use App\Form\BrokerConfigType;
use Symfony\Component\Uid\Uuid;
use App\Util\JsonPlaceholders;
use App\Service\ClearDataService;
use App\Repository\BrokerRepository;
use App\Service\PolicyImportService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\BrokerConfigRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

#[Route('/{format<admin|api>}/brokers')]
#[IsGranted('ROLE_ADMIN')]
class BrokerConfigController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private BrokerRepository $brokerRepository;
    private BrokerConfigRepository $brokerConfigRepository;
    private PolicyImportService $policyImportService;
    private ClearDataService $clearDataService;
    private CacheHelper $cacheHelper;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        BrokerRepository $brokerRepository,
        BrokerConfigRepository $brokerConfigRepository,
        PolicyImportService $policyImportService,
        ClearDataService $clearDataService,
        CacheHelper $cacheHelper,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->brokerRepository = $brokerRepository;
        $this->brokerConfigRepository = $brokerConfigRepository;
        $this->policyImportService = $policyImportService;
        $this->clearDataService = $clearDataService;
        $this->cacheHelper = $cacheHelper;
        $this->validator = $validator;
    }

    /**
     * Get/List broker configurations (UI + API)
     */
    #[Route('/config/list', name: 'broker_config_index', methods: ['GET'])]
    public function index(Request $request, string $format): Response
    {
        $brokers = $this->brokerRepository->findAll();

        if ($format === 'api') {
            return $this->json(array_map(fn (Broker $broker) => [
                'uuid' => $broker->getUuid(),
                'broker_name' => $broker->getName(),
                'file_name' => $broker->getConfig()->getFileName(),
                'file_mapping' => $broker->getConfig()->getFileMapping(),
            ], $brokers));
        }

        return $this->render('broker_config/index.html.twig', [
            'brokers' => $brokers
        ]);
    }

    /**
     * Create or Edit a broker configuration (UI + API)
     */
    #[Route('/config/{uuid?}', name: 'broker_config_save', methods: ['GET', 'POST', 'PUT'])]
    public function save(Request $request, string $format, ?string $uuid = null): Response
    {
        $isEdit = $uuid !== null;
        $config = $isEdit ? $this->getBrokerConfigByUuid($uuid) : new BrokerConfig();

        if ($format === 'api') {
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
            if ($this->brokerConfigRepository->findOneBy(['broker' => $broker])) {
                return $this->json(['error' => 'Broker config already exists'], JsonResponse::HTTP_CONFLICT);
            }

            $config->setFileName($data['file_name'] ?? $config->getFileName());
            $config->setFileMapping($data['file_mapping'] ?? $config->getFileMapping());

            return $this->persistAndReturnResponse($config, 'Broker configuration saved successfully', $isEdit ? JsonResponse::HTTP_OK : JsonResponse::HTTP_CREATED);
        }

        return $this->handleForm($request, $config, $isEdit);
    }

    /**
     * Delete a broker configuration (UI + API)
     */
    #[Route('/config/delete/{uuid}', name: 'broker_config_delete', methods: ['POST'])]
    public function delete(Request $request, string $format, string $uuid): Response
    {
        $broker = $this->brokerRepository->findOneBy(['uuid' => $uuid]);
        if (!$broker) {
            return $this->handleNotFound($format, 'Broker not found.');
        }

        if ($format === 'admin' && !$this->isCsrfTokenValid('delete' . $broker->getUuId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('broker_config_index', ['format' => 'admin']);
        }

        $this->entityManager->remove($broker);
        $this->entityManager->flush();

        if ($format === 'admin') {
            $this->addFlash('success', 'Broker configuration deleted successfully.');
        }

        return $format === 'api'
            ? $this->json(['message' => 'Broker configuration deleted successfully'])
            : $this->redirectToRoute('broker_config_index', ['format' => 'admin']);
    }

    /**
     * Handle CSV Upload (UI + API)
     */
    #[Route('/config/upload/{uuid}', name: 'broker_upload_csv', methods: ['POST'])]
    public function uploadCsv(Request $request, string $format, string $uuid): Response
    {
        $broker = $this->getBrokerByUuid($uuid);
        if (!$broker) {
            return $this->handleNotFound($format, 'Broker not found.');
        }

        $file = $request->files->get('csv_file');
        if (!$file) {
            return $this->handleError($format, 'No file uploaded.');
        }

        try {
            $this->policyImportService->handleFileUpload($file, $broker);
            return $this->handleSuccess($format, 'CSV file processed successfully.');
        } catch (\Exception $e) {
            return $this->handleError($format, 'Error processing CSV file: ' . $e->getMessage());
        }
    }

    /**
     * Helper Methods
     */

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

    private function getBrokerByUuid(string $uuid): ?Broker
    {
        if (!Uuid::isValid($uuid)) {
            throw new BadRequestException('Invalid UUID format.');
        }

        $broker = $this->brokerRepository->findOneBy(['uuid' => $uuid]);

        if (!$broker) {
            throw new NotFoundHttpException('Broker not found.');
        }

        return $broker;
    }
    private function getBrokerConfigByUuid(string $uuid): BrokerConfig
    {
        $broker = $this->brokerRepository->findOneBy(['uuid' => $uuid]);

        if (!$broker || !$broker->getConfig()) {
            throw $this->createNotFoundException("Broker configuration not found.");
        }

        return $broker->getConfig();
    }

    private function handleForm(Request $request, BrokerConfig $config, bool $isEdit): Response
    {
        if (!$isEdit) {
            $config->setFileMapping(json_decode(JsonPlaceholders::BROKER_CONFIG, true));
        }
        
        $form = $this->createForm(BrokerConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($config);
            $this->entityManager->flush();

            $this->addFlash('success', $isEdit ? 'Updated successfully' : 'Created successfully');
            return $this->redirectToRoute('broker_config_index', ['format' => 'admin']);
        }

        return $this->render('broker_config/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => $isEdit
        ]);
    }

    private function persistAndReturnResponse(BrokerConfig $config, string $message, int $status = JsonResponse::HTTP_OK): JsonResponse
    {
        $errors = $this->validator->validate($config);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($config);
        $this->entityManager->flush();

        return $this->json(['message' => $message], $status);
    }

    private function handleNotFound(string $format, string $message): Response
    {
        return $format === 'api' ? $this->json(['error' => $message], JsonResponse::HTTP_NOT_FOUND) : $this->redirectToRoute('broker_config_index', ['format' => 'admin']);
    }

    private function handleError(string $format, string $message): Response
    {
        return $format === 'api' ? $this->json(['error' => $message], JsonResponse::HTTP_BAD_REQUEST) : $this->redirectToRoute('broker_config_index', ['format' => 'admin']);
    }

    private function handleSuccess(string $format, string $message): Response
    {
        return $format === 'api' ? $this->json(['message' => $message]) : $this->redirectToRoute('broker_config_index', ['format' => 'admin']);
    }
}
