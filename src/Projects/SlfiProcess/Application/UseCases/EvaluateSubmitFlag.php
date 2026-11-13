<?php

declare(strict_types=1);

namespace ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Application\UseCases;

use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\InvestorRepositoryPort;

final class EvaluateSubmitFlag
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
        $pending = $this->investors->countWithStatuses($applicationUid, ['Pending', 'Sent', 'Re-sent']);
        $showFlag = $pending > 0 ? 'NO' : 'YES';

        return array_merge($data, ['SLFI_FLAG_SUBMIT' => $showFlag]);
    }
}
