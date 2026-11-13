<?php

declare(strict_types=1);

namespace ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports;

interface ConfigurationRepositoryPort
{
  public function getVariableValue(string $variableName): ?string;

  public function getActiveMinExpirationDays(): int;

  public function getLatestZipMessageDate(int $caseNumber): ?string;
}
