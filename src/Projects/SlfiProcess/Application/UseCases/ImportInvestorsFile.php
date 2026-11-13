<?php

declare(strict_types=1);

namespace ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Application\UseCases;

use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\InvestorRepositoryPort;

final class ImportInvestorsFile
{
    public function __construct(
        private InvestorRepositoryPort $investors
    ) {
    }

  /**
   * @param array<string, mixed> $data
   * @return array<string, mixed>
   */
    public function handle(array $data): array
    {
        $applicationUid = (string) ($data['application'] ?? $data['APPLICATION'] ?? '');
        if ($applicationUid === '') {
            return $data;
        }

        $upload = $data['SLFI_UPLOAD_INVESTORS'] ?? null;
        $rows = $this->parseUpload($upload);
        if ($rows !== []) {
            $this->investors->replaceForApplication($applicationUid, $rows);
        }

        $grid = $this->investors->listByApplication($applicationUid);

        return array_merge($data, [
            'SLFI_INVESTORS_GRID' => $grid,
            'SLFI_BACK_FORM' => 1,
        ]);
    }

  /**
   * @param mixed $upload
   * @return list<array<string, mixed>>
   */
    private function parseUpload($upload): array
    {
        if (!is_array($upload) || $upload === []) {
            return [];
        }
        $path = $upload[0]['path'] ?? $upload['path'] ?? null;
        if (!is_string($path) || !is_readable($path)) {
            return [];
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return [];
        }

        $rows = [];
        $header = null;
        while (($line = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map('strtolower', $line);
                continue;
            }
            if (count($line) < 2) {
                continue;
            }
            $row = array_combine($header, $line);
            if ($row === false) {
                continue;
            }
            $rows[] = [
                'SID_EMAIL' => $row['email'] ?? $row['sid_email'] ?? $line[0],
                'SID_DISPLAY_NAME' => $row['display name'] ?? $row['display_name'] ?? $line[1] ?? '',
                'SID_INVESTOR_NAME' => $row['investor name'] ?? $row['investor_name'] ?? '',
                'SID_INVESTOR_ID' => $row['investor id'] ?? $row['investor_id'] ?? '',
                'SID_CLASS' => $row['class'] ?? '',
                'SID_STATUS' => 'Pending',
            ];
        }
        fclose($handle);

        return $rows;
    }
}
