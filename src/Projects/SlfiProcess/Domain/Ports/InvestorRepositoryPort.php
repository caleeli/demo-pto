<?php

declare(strict_types=1);

namespace ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports;

interface InvestorRepositoryPort
{
  /**
   * @return list<array<string, mixed>>
   */
  public function listByApplication(string $applicationUid): array;

  /**
   * @return list<array<string, mixed>>
   */
  public function listTrackingByApplication(string $applicationUid): array;

  public function deleteByApplication(string $applicationUid): void;

  /**
   * @param list<array<string, mixed>> $rows
   */
  public function replaceForApplication(string $applicationUid, array $rows): void;

  public function countWithStatuses(string $applicationUid, array $statuses): int;

  public function countCompleted(string $applicationUid): int;
}
