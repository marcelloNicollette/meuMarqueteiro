<?php

namespace App\Services\Support;

use App\Services\Radar\RadarSyncExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CoverageAlertExportService
{
    public function __construct(
        private readonly RadarSyncExportService $export,
    ) {}

    public function downloadCsv(string $filename, array $filterRows, array $headers, array $rows): StreamedResponse
    {
        return $this->export->downloadCsv($filename, $filterRows, $headers, $rows);
    }

    public function downloadXlsx(string $filename, array $sheets): BinaryFileResponse
    {
        return $this->export->downloadXlsx($filename, $sheets);
    }
}
