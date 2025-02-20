<?php

namespace App\Logger;

use App\Interface\PolicyImportLoggerInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class FlashPolicyImportLogger implements PolicyImportLoggerInterface
{
    private FlashBagInterface $flashBag;

    public function __construct(FlashBagInterface $flashBag)
    {
        $this->flashBag = $flashBag;
    }

    public function log($level, $message, array $context = []): void
    {
        $this->flashBag->add($level, $message);
    }

    public function info(string $message): void
    {
        $this->flashBag->add('info', $message);
    }

    public function warning(string $message): void
    {
        $this->flashBag->add('warning', $message);
    }

    public function error(string $message): void
    {
        $this->flashBag->add('error', $message);
    }

    public function success(string $message): void
    {
        $this->flashBag->add('success', $message);
    }
}
