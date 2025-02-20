<?php

namespace App\Service;

use League\Csv\Reader;

class CsvService
{
    public function readCsvFile(string $filePath): Reader
    {
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);
        return $csv;
    }

    public function validateMapping(array $fileMapping, array $csvHeaders): bool
    {
        foreach ($fileMapping as $configKey => $csvHeader) {
            if (!in_array($csvHeader, $csvHeaders, true)) {
                return false;
            }
        }
        return true;
    }
}
