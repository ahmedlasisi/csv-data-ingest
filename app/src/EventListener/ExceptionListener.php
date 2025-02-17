<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\ORMException;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpFoundation\Request;

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
        $request = $event->getRequest();
        $exception = $event->getThrowable();

        // Restrict JSON response to API routes only
        if (!$this->isApiRequest($request)) {
            return; // Allow Symfony to handle the error normally (e.g., render Twig error pages)
        }

        // Log error (optional)
        $this->logger->error($exception->getMessage());

        // Check the environment (only show detailed error messages in dev)
        $isDevEnv = $this->params->get('kernel.environment') === 'dev';

        // Default status code and message
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        $message = $exception instanceof HttpExceptionInterface ? $exception->getMessage() : 'Internal Server Error';

        // Handle Doctrine related exceptions specifically
        if ($exception instanceof Exception || $exception instanceof ORMException) {
            $message = $this->getDoctrineErrorMessage($exception);
        }

        // If in development environment, show detailed exception messages
        if ($isDevEnv) {
            $message .= ' | ' . $exception->getMessage();
        }

        $response = new JsonResponse([
            'error' => $message
        ], $statusCode);

        $event->setResponse($response);
    }

    /**
     * Check if the request is for an API route.
     * This assumes all API routes start with "/api".
     */
    private function isApiRequest(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api');
    }

    private function getDoctrineErrorMessage(\Throwable $exception): string
    {
        if ($exception instanceof \Doctrine\ORM\Query\QueryException) {
            return $exception->getMessage();
        }

        return 'A database error occurred: ' . $exception->getMessage();
    }
}
