<?php

declare(strict_types=1);

namespace ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Application\UseCases;

use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\InvestorRepositoryPort;

final class CancelCaseCleanup
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
        $flag = (string) ($data['SLFI_FLAG_CANCEL_CASE'] ?? '');
        if ($flag !== '' && $flag !== 'RETURN') {
            $applicationUid = (string) ($data['application'] ?? $data['APPLICATION'] ?? '');
            if ($applicationUid !== '') {
                $this->investors->deleteByApplication($applicationUid);
            }
        }

        return $data;
    }
}
