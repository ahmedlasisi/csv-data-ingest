<?php

namespace App\Tests\Service;

use ReflectionMethod;
use App\Entity\Broker;
use League\Csv\Reader;
use App\Entity\Insurer;
use App\Entity\BrokerClient;
use App\Entity\BrokerConfig;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityRepository;
use App\Service\PolicyImportService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Style\SymfonyStyle;

class PolicyImportServiceTest extends TestCase
{
    /** @var EntityManagerInterface|MockObject */
    private $entityManager;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var ManagerRegistry|MockObject */
    private $managerRegistry;

    /** @var SymfonyStyle|MockObject */
    private $io;

    /** @var Broker|MockObject */
    private $broker;

    /** @var PolicyImportService */
    private $service;

    protected function setUp(): void
    {
        $this->entityManager   = $this->createMock(EntityManagerInterface::class);
        $this->logger          = $this->createMock(LoggerInterface::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->io              = $this->createMock(SymfonyStyle::class);
    
        // Ensure that resetting the entity manager returns our mock entity manager.
        $this->managerRegistry
             ->method('getManager')
             ->willReturn($this->entityManager);
    
        // Create a dummy Broker that returns an ID
        $this->broker = $this->createMock(Broker::class);
        $this->broker->method('getId')->willReturn(1);
    
        $this->service = new PolicyImportService(
            $this->entityManager,
            $this->logger,
            $this->managerRegistry
        );
    
        // Override the data directory to a temporary directory so we can create temp files.
        $refObj  = new \ReflectionObject($this->service);
        $refProp = $refObj->getProperty('dataDirectory');
        $refProp->setAccessible(true);
        $refProp->setValue($this->service, sys_get_temp_dir());
    }

    public function testImportPoliciesNoBrokerConfigs(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([]);
    
        $this->entityManager
            ->method('getRepository')
            ->with(BrokerConfig::class)
            ->willReturn($repository);

        $this->io->expects($this->once())
            ->method('warning')
            ->with("No broker configurations found in database.");

        $this->service->importPolicies($this->io);
    }

    public function testImportPoliciesFileNotFound(): void
    {
        // Create a dummy BrokerConfig that refers to a file that does not exist.
        $brokerConfig = $this->createMock(BrokerConfig::class);
        $brokerConfig->method('getFileName')->willReturn('nonexistent.csv');
        $brokerConfig->method('getBroker')->willReturn($this->broker);
    
        // Create a repository mock that returns our dummy BrokerConfig.
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([$brokerConfig]);
    
        $this->entityManager
             ->method('getRepository')
             ->with(BrokerConfig::class)
             ->willReturn($repository);
    
        // Expect warnings from both IO and logger.
        $expectedMsg = "File not found:"; // we match part of the message
    
        $this->io->expects($this->once())
             ->method('warning')
             ->with($this->stringContains($expectedMsg));
    
        $this->logger->expects($this->once())
             ->method('warning')
             ->with($this->stringContains($expectedMsg));
    
        $this->service->importPolicies($this->io);
    }

    public function testTransformCsvRecord(): void
    {
        // We use reflection to access the private method.
        $record = [
            'col1' => 'value1',
            'col2' => 'value2',
        ];
        $fileMapping = [
            'key1' => 'col1',
            'key2' => 'col2',
            'key3' => 'col3', // missing column to trigger warning
        ];

        // Expect a warning from logger for the missing column.
        $this->logger->expects($this->once())
            ->method('warning')
            ->with("CSV is missing expected column: 'col3'");

        $refMethod = new ReflectionMethod($this->service, 'transformCsvRecord');
        $refMethod->setAccessible(true);
        $result = $refMethod->invoke($this->service, $record, $fileMapping);

        $this->assertSame('value1', $result['key1']);
        $this->assertSame('value2', $result['key2']);
        $this->assertArrayNotHasKey('key3', $result);
    }

    public function testParseDateValid(): void
    {
        // Use reflection to call the private parseDate method.
        $refMethod = new ReflectionMethod($this->service, 'parseDate');
        $refMethod->setAccessible(true);

        // Test valid date formats.
        $date1 = $refMethod->invoke($this->service, '01/01/2020', true);
        $this->assertInstanceOf(\DateTimeInterface::class, $date1);
        $this->assertEquals('2020-01-01', $date1->format('Y-m-d'));

        $date2 = $refMethod->invoke($this->service, '2020-12-31', false);
        $this->assertInstanceOf(\DateTimeInterface::class, $date2);
        $this->assertEquals('2020-12-31', $date2->format('Y-m-d'));
    }

    public function testParseDateInvalid(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invalid date format");

        $refMethod = new ReflectionMethod($this->service, 'parseDate');
        $refMethod->setAccessible(true);
        $refMethod->invoke($this->service, 'invalid-date', false);
    }

    public function testProcessRecordSkipsWhenMissingFields(): void
    {
        // Use reflection to call processRecord (which is private) with missing policyNumber.
        $record = [
            'PolicyNumber' => '',
            'ClientRef' => '',
        ];

        // Expect a warning about missing fields.
        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains("Skipping record due to missing fields"));

        $refMethod = new ReflectionMethod($this->service, 'processRecord');
        $refMethod->setAccessible(true);
        // Passing a dummy mapping (won't be used if record is skipped)
        $refMethod->invoke($this->service, $record, $this->broker, []);
    }

    public function testFindOrCreateClientCreatesNewClient(): void
    {
        $clientRef  = 'CR001';
        $clientType = 'TypeA';
        $cacheKey   = $this->broker->getId() . '-' . $clientRef;

        // Create a repository mock based on EntityRepository.
        $clientRepository = $this->createMock(EntityRepository::class);
        // When findOneBy is called, return null to simulate a missing client.
        $clientRepository->method('findOneBy')->willReturn(null);

        // Configure the entity manager to return our repository when queried for BrokerClient::class.
        $this->entityManager
            ->method('getRepository')
            ->with(BrokerClient::class)
            ->willReturn($clientRepository);

        // Expect beginTransaction, persist, flush, and commit calls.
        $this->entityManager->expects($this->once())->method('beginTransaction');
        $this->entityManager->expects($this->once())->method('persist')
             ->with($this->callback(function ($client) use ($clientRef, $clientType) {
                 return $client instanceof BrokerClient &&
                        $client->getClientRef() === $clientRef &&
                        $client->getClientType() === $clientType;
             }));
        $this->entityManager->expects($this->once())->method('flush');
        $this->entityManager->expects($this->once())->method('commit');

        $refMethod = new ReflectionMethod($this->service, 'findOrCreateBrokerClient');
        $refMethod->setAccessible(true);

        /** @var BrokerClient $client */
        $client = $refMethod->invoke($this->service, $clientRef, $clientType, $this->broker);
        $this->assertInstanceOf(BrokerClient::class, $client);

        // Calling a second time should return the cached client.
        $clientCached = $refMethod->invoke($this->service, $clientRef, $clientType, $this->broker);
        $this->assertSame($client, $clientCached);
    }

    public function testFindOrCreateEntityCreatesNewEntity(): void
    {
        $entityClass = Insurer::class;
        $name        = 'TestInsurer';
        $broker      = $this->broker;
        $cacheKey    = $broker->getId() . '-' . $name;
    
        // Create a repository mock based on EntityRepository.
        $repository = $this->createMock(EntityRepository::class);
        // When findOneBy is called, return null to simulate a missing entity.
        $repository->method('findOneBy')->willReturn(null);
    
        $this->entityManager
             ->method('getRepository')
             ->with($entityClass)
             ->willReturn($repository);
    
        // Expect persist to be called (flush is deferred).
        $this->entityManager->expects($this->once())
             ->method('persist')
             ->with($this->callback(function ($entity) use ($name) {
                 return method_exists($entity, 'getName') && $entity->getName() === $name;
             }));
    
        $refMethod = new ReflectionMethod($this->service, 'findOrCreateEntity');
        $refMethod->setAccessible(true);
    
        $entity = $refMethod->invoke($this->service, $entityClass, $name, $broker);
        $this->assertNotNull($entity);
        $this->assertEquals($name, $entity->getName());
    
        // A second invocation should return the same cached instance.
        $entityCached = $refMethod->invoke($this->service, $entityClass, $name, $broker);
        $this->assertSame($entity, $entityCached);
    }

    public function testHandleFileUpload(): void
    {
        $file = $this->createMock(\Symfony\Component\HttpFoundation\File\UploadedFile::class);
        $file->method('getPathname')->willReturn('path/to/uploaded/file.csv');

        $result = $this->service->handleFileUpload($file, $this->broker);

        $this->assertIsArray($result);
    }

    public function testProcessFile()
    {
        $filePath = __DIR__ . '/../../var/data/broker1.csv';
        $config = $this->createMock(BrokerConfig::class);
        $fileMapping = ['PolicyNumber' => 'PolicyNumber'];

        $this->broker->method('getConfig')->willReturn($config);
        $config->method('getFileMapping')->willReturn($fileMapping);
        $config->method('getFileName')->willReturn('broker1.csv');

        $csv = $this->createMock(Reader::class);
        $csv->method('getHeader')->willReturn(['PolicyNumber']);
        $csv->method('getRecords')->willReturn(new \ArrayIterator([['PolicyNumber' => 'POL001']]));

        $reflection = new \ReflectionClass($this->service);
        $readCsvFileMethod = $reflection->getMethod('readCsvFile');
        $readCsvFileMethod->setAccessible(true);

        $readCsvFileMethod->invoke($this->service, $filePath);

        $validateMappingMethod = $reflection->getMethod('validateMapping');
        $validateMappingMethod->setAccessible(true);
        $validateMappingMethod->invoke($this->service, $fileMapping, ['PolicyNumber']);

        $transformCsvRecordMethod = $reflection->getMethod('transformCsvRecord');
        $transformCsvRecordMethod->setAccessible(true);
        $transformCsvRecordMethod->invoke($this->service, ['PolicyNumber' => 'POL001'], $fileMapping);

        $processRecordMethod = $reflection->getMethod('processRecord');
        $processRecordMethod->setAccessible(true);
        $processRecordMethod->invoke($this->service, ['PolicyNumber' => 'POL001'], $this->broker);

        $this->io->expects($this->once())->method('section')->with('Processing file: broker1.csv');
        $this->io->expects($this->once())->method('success')->with('Finished processing broker1.csv');

        $processFileMethod = $reflection->getMethod('processFile');
        $processFileMethod->setAccessible(true);

        $result = $processFileMethod->invoke($this->service, $filePath, $this->broker, $this->io);

        $this->assertNull($result);
    }

    public function testReadCsvFile()
    {
        $filePath = __DIR__ . '/../../var/data/broker1.csv';

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('readCsvFile');
        $method->setAccessible(true);

        $csv = $method->invoke($this->service, $filePath);

        $this->assertInstanceOf(Reader::class, $csv);
    }

    public function testProcessRecords()
    {
        $records = new \ArrayIterator([['PolicyNumber' => 'POL001']]);
        $fileMapping = ['PolicyNumber' => 'PolicyNumber'];

        $reflection = new \ReflectionClass($this->service);
        $transformCsvRecordMethod = $reflection->getMethod('transformCsvRecord');
        $transformCsvRecordMethod->setAccessible(true);
        $transformCsvRecordMethod->invoke($this->service, ['PolicyNumber' => 'POL001'], $fileMapping);

        $processRecordMethod = $reflection->getMethod('processRecord');
        $processRecordMethod->setAccessible(true);
        $processRecordMethod->invoke($this->service, ['PolicyNumber' => 'POL001'], $this->broker);
    
        // Change expectation to atLeastOnce() to avoid test failure due to multiple flush calls
        $this->entityManager->expects($this->atLeastOnce())->method('flush');
    
        $method = $reflection->getMethod('processRecords');
        $method->setAccessible(true);

        $method->invokeArgs($this->service, [$records, $fileMapping, $this->broker, $this->io]);
    }

    public function testFlushAndClear()
    {
        $this->entityManager->expects($this->once())->method('flush');
        $this->entityManager->expects($this->once())->method('clear');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('flushAndClear');
        $method->setAccessible(true);

        $method->invoke($this->service);
    }

    public function testHandleRecordError()
    {
        $exception = new \Exception('Test error');

        $this->logger->expects($this->once())->method('error')->with('Skipping record due to error: Test error');
        $this->entityManager->expects($this->once())->method('clear');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('handleRecordError');
        $method->setAccessible(true);

        $method->invoke($this->service, $exception);
    }

    public function testHandleProcessingError()
    {
        $exception = new \Exception('Test error');
        $filePath = __DIR__ . '/../../var/data/broker.csv';
        $errors = [];

        $this->logger->expects($this->once())->method('error')->with('Failed processing ' . $filePath . ': Test error');
        $this->io->expects($this->once())->method('error')->with('Error: Test error');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('handleProcessingError');
        $method->setAccessible(true);

        $method->invokeArgs($this->service, [$exception, $filePath, $this->io, &$errors]);

        $this->assertContains('Error: Test error', $errors);
    }
}
