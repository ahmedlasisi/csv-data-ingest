<?php

namespace App\Interface;

interface PolicyImportLoggerInterface
{
    public function log($level, $message, array $context = []): void;
    public function info(string $message): void;
    public function warning(string $message): void;
    public function error(string $message): void;
    public function success(string $message): void;
}
