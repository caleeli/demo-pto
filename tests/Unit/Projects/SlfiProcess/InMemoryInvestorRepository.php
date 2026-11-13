<?php

declare(strict_types=1);

namespace Tests\Unit\Projects\SlfiProcess;

use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\InvestorRepositoryPort;

final class InMemoryInvestorRepository implements InvestorRepositoryPort
{
  /** @var array<string, list<array<string, mixed>>> */
    private array $store = [];

  /**
   * @return list<array<string, mixed>>
   */
    public function listByApplication(string $applicationUid): array
    {
        return $this->store[$applicationUid] ?? [];
    }

  /**
   * @return list<array<string, mixed>>
   */
    public function listTrackingByApplication(string $applicationUid): array
    {
        return $this->listByApplication($applicationUid);
    }

    public function deleteByApplication(string $applicationUid): void
    {
        unset($this->store[$applicationUid]);
    }

  /**
   * @param list<array<string, mixed>> $rows
   */
    public function replaceForApplication(string $applicationUid, array $rows): void
    {
        $this->store[$applicationUid] = $rows;
    }

    public function countWithStatuses(string $applicationUid, array $statuses): int
    {
        $count = 0;
        foreach ($this->store[$applicationUid] ?? [] as $row) {
            if (in_array($row['SID_STATUS'] ?? '', $statuses, true)) {
                $count++;
            }
        }

        return $count;
    }

    public function countCompleted(string $applicationUid): int
    {
        $count = 0;
        foreach ($this->store[$applicationUid] ?? [] as $row) {
            $status = (string) ($row['SID_STATUS'] ?? '');
            if (str_starts_with($status, 'Completed')) {
                $count++;
            }
        }

        return $count;
    }
}
