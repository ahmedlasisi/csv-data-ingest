<?php

namespace App\Controller;

use App\Entity\Broker;
use App\Entity\BrokerConfig;
use App\Service\CacheHelper;
use App\Form\BrokerConfigType;
use App\Util\JsonPlaceholders;
use Symfony\Component\Uid\Uuid;
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
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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

    #[Route('/config/new', name: 'broker_config_create', methods: ['GET', 'POST', 'PUT'])]
    public function createBrokerConfig(Request $request, string $format): Response
    {
        return $this->handleBrokerConfig($request, $format, null);
    }

    #[Route('/config/{uuid}', name: 'broker_config_edit', methods: ['GET', 'POST', 'PUT'])]
    public function editBrokerConfig(Request $request, string $format, string $uuid): Response
    {
        return $this->handleBrokerConfig($request, $format, $uuid);
    }

    #[Route('/config/delete/{uuid}', name: 'broker_config_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, string $format, string $uuid): Response
    {
        $broker = $this->brokerRepository->findOneBy(['uuid' => $uuid]);
        if (!$broker) {
            return $this->handleNotFound($format, 'Broker not found.');
        }

        if ($format === 'admin' && !$this->isCsrfTokenValid('delete' . $broker->getUuId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('broker_config_index', ['format' => 'admin']);
        }

        $this->clearDataService->clearBrokerData($broker);

        $this->entityManager->remove($broker);
        $this->entityManager->flush();
        $message ='Broker configuration and data deleted successfully';

        return $this->handleSuccess($format, $message);
    }

    #[Route('/config/upload/{uuid}', name: 'broker_upload_csv', methods: ['POST'])]
    public function uploadCsv(Request $request, string $format, string $uuid): JsonResponse
    {
        $broker = $this->getEntityByUuid($uuid, Broker::class, $format);

        if (!($broker instanceof Broker)) {
            return $broker;
        }
       
        $file = $request->files->get('csv_file');
        if (!$file || !$broker) {
            return $this->handleError($format, 'Invalid file or broker.');
        }
        
        $response = $this->policyImportService->handleFileUpload($file, $broker);

        if ($format === 'admin') {
            $this->addFlash('success', 'CSV file uploaded successfully');
        }

        return $response;
    }

    #[Route('/{uuid}/clear-policies', name: 'api_clear_broker_policy_data', methods: ['DELETE'])]
    public function clearBrokerPolicies(string $uuid, string $format): JsonResponse
    {
        return $this->clearBrokerDataByType($uuid, 'policies', $format);
    }

    #[Route('/{uuid}/clear-all', name: 'api_clear_all_broker_data', methods: ['DELETE'])]
    public function clearBrokerData(string $uuid, string $format): JsonResponse
    {
        return $this->clearBrokerDataByType($uuid, 'all', $format);
    }

    private function handleBrokerConfig(Request $request, string $format, ?string $uuid): Response | JsonResponse
    {
        $isEdit = $uuid !== null;

        if ($isEdit) {
            $broker = $this->getEntityByUuid($uuid, Broker::class, $format);

            if (!($broker instanceof Broker)) {
                return $broker;
            }
        }
        $config = $isEdit ? $broker->getConfig() : new BrokerConfig();

        if ($format === 'api') {
            $data = $this->getJsonRequestBody($request, ['broker_name', 'file_name', 'file_mapping']);
            if ($data instanceof JsonResponse) {
                return $data;
            }

            if (!$this->policyImportService->validateConfigMapping($data['file_mapping'])) {
                return $this->handleError($format, 'Invalid JSON Config mapping for file');
            }

            $broker = $isEdit ? $broker->setName($data['broker_name']) : $this->entityManager->getRepository(Broker::class)->findOneBy(['name' => trim($data['broker_name'])]);
            if (!$broker) {
                $broker = $this->policyImportService->findOrCreateEntity(Broker::class, trim($data['broker_name']), $broker);
            }

            if (!$isEdit && $this->brokerConfigRepository->findOneBy(['broker' => $broker])) {
                return $this->handleError($format, 'Broker config already exists');
            }

            $config->setBroker($broker);
            $config->setFileName($data['file_name'] ?? $config->getFileName());
            $config->setFileMapping($data['file_mapping'] ?? $config->getFileMapping());
 
            return $this->persistAndReturnResponse($config, 'Broker configuration saved successfully', $isEdit ? JsonResponse::HTTP_OK : JsonResponse::HTTP_CREATED);
        }

        return $this->handleForm($request, $config, $isEdit);
    }

    private function clearBrokerDataByType(string $uuid, string $dataType, string $format): JsonResponse
    {
        $broker = $this->getEntityByUuid($uuid, Broker::class, $format);

        if (!($broker instanceof Broker)) {
            return $broker;
        }
       
        try {
            // Choose the correct service method based on the data type
            if ($dataType === 'policies') {
                $this->clearDataService->clearBrokerPoliciesData($broker);
                $message = "Cleared policies for broker: {$broker->getName()}";
            } elseif ($dataType === 'all') {
                $this->clearDataService->clearBrokerData($broker);
                $message = "Cleared all data for broker: {$broker->getName()}";
            } else {
                throw new \InvalidArgumentException('Invalid data type');
            }

            //     $this->addFlash('success', 'Broker policy data cleared successfully');

            //     return $this->json(['message' => $message], JsonResponse::HTTP_OK);
            // } catch (\Exception $e) {
            //     return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            // }

            if ($format === 'admin') {
                $this->addFlash('success', $message);
                return new JsonResponse(['redirect' => $this->generateUrl('broker_config_index', ['format' => 'admin'])]);
            }
       
            return $this->json(['message' => $message], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            if ($format === 'admin') {
                $this->addFlash('error', $e->getMessage());
                return new JsonResponse(['redirect' => $this->generateUrl('broker_config_index', ['format' => 'admin'])]);
            }
       
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getJsonRequestBody(Request $request, array $requiredFields = []): array|JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->handleError('api', 'Invalid JSON request body');
        }

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->handleError('api', "Missing required field: $field");
            }
        }

        return $data;
    }

    public function getEntityByUuid(string $uuid, string $entityClass, string $format)
    {
        if (!Uuid::isValid($uuid)) {
            return $this->handleError($format, 'Invalid UUID format.');
        }

        $repository = $this->entityManager->getRepository($entityClass);

        $entity = $repository->findOneBy(['uuid' => $uuid]);

        if (!$entity) {
            return $this->handleNotFound($format, $entityClass.' Entity not found.');
        }
        
        return $entity;
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
            return $this->handleError('api', (string) $errors);
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
        if ($format === 'api') {
            return $this->json(['message' => $message]);
        }

        $this->addFlash('success', $message);
        return $this->redirectToRoute('broker_config_index', ['format' => 'admin']);
    }
}
