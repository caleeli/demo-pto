<?php

declare(strict_types=1);

namespace ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Application\UseCases;

use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\ConfigurationRepositoryPort;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\InvestorRepositoryPort;

final class EvaluateZipButton
{
    public function __construct(
        private ConfigurationRepositoryPort $configuration,
        private InvestorRepositoryPort $investors
    ) {
    }

  /**
   * @param array<string, mixed> $data
   * @return array<string, mixed>
   */
    public function handle(array $data): array
    {
        $disabled = 0;
        $caseNumber = (int) ($data['case_number'] ?? $data['APP_NUMBER'] ?? 0);
        $applicationUid = (string) ($data['application'] ?? $data['APPLICATION'] ?? '');

        $emailDate = $this->configuration->getLatestZipMessageDate($caseNumber);
        if ($emailDate !== null && $emailDate !== '') {
            $emailDateAddOneDay = strtotime($emailDate . ' +1 day');
            $currentDate = time();
            if ($emailDateAddOneDay !== false && $currentDate < $emailDateAddOneDay) {
                $disabled = 1;
            }
        }

        if ($this->investors->countCompleted($applicationUid) === 0) {
            $disabled = 1;
        }

        return array_merge($data, ['SLFI_DISABLED_BUTTON_INVESTORS_ZIP' => $disabled]);
    }
}
