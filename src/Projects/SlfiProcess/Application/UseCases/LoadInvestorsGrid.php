<?php

declare(strict_types=1);

namespace ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Application\UseCases;

use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\InvestorRepositoryPort;

final class LoadInvestorsGrid
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

        return array_merge($data, [
            'SLFI_INVESTORS_GRID' => $this->investors->listByApplication($applicationUid),
        ]);
    }
}
