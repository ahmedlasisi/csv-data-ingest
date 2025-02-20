<?php

namespace App\Logger;

use App\Interface\PolicyImportLoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsolePolicyImportLogger implements PolicyImportLoggerInterface
{
    private ?SymfonyStyle $io = null;

    public function setSymfonyStyle(SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    public function info(string $message): void
    {
        if ($this->io) {
            $this->io->text($message);
        }
    }

    public function warning(string $message): void
    {
        if ($this->io) {
            $this->io->warning($message);
        }
    }

    public function error(string $message): void
    {
        if ($this->io) {
            $this->io->error($message);
        }
    }

    public function success(string $message): void
    {
        if ($this->io) {
            $this->io->success($message);
        }
    }
}
