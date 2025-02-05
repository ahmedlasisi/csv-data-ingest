<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\ORMException;
use Doctrine\DBAL\Exception;

class ExceptionListener
{
    private LoggerInterface $logger;
    private ParameterBagInterface $params;

    public function __construct(LoggerInterface $logger, ParameterBagInterface $params)
    {
        $this->logger = $logger;
        $this->params = $params;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Log error (optional)
        $this->logger->error($exception->getMessage());

        // Check the environment (only show detailed error messages in dev environment)
        $isDevEnv = $this->params->get('kernel.environment') === 'dev';

        // Default status code and message
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        $message = $exception instanceof HttpExceptionInterface ? $exception->getMessage() : 'Internal Server Error';

        // Handle Doctrine related exceptions specifically
        if ($exception instanceof Exception || $exception instanceof ORMException) {
            // For DBAL and ORM errors, we get the message and format it to only show the core issue
            $message = $this->getDoctrineErrorMessage($exception);
        }

        // If in development environment, show detailed exception messages
        if ($isDevEnv) {
            // Append stack trace for dev environment (optional)
            $message .= ' | ' . $this->getDoctrineErrorMessage($exception);
        }

        $response = new JsonResponse([
            'error' => $message
        ], $statusCode);

        $event->setResponse($response);
    }

    private function getDoctrineErrorMessage(\Throwable $exception): string
    {
        // Check for common Doctrine exceptions and format the message for better readability
        if ($exception instanceof Doctrine\ORM\Query\QueryException) {
            // Extract only the core message (e.g., semantical error)
            return $exception->getMessage();
        }

        // Fallback for other Doctrine exceptions
        return 'A database error occurred: ' . $exception->getMessage();
    }
}
