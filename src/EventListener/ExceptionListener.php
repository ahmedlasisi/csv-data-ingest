<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Psr\Log\LoggerInterface;

class ExceptionListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Log error (optional)
        $this->logger->error($exception->getMessage());

        // Default status code and message
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        $message = $exception instanceof HttpExceptionInterface ? $exception->getMessage() : 'Internal Server Error';

        $response = new JsonResponse([
            'error' => $message
        ], $statusCode);

        $event->setResponse($response);
    }
}
