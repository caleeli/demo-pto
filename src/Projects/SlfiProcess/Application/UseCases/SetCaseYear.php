<?php

declare(strict_types=1);

namespace ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Application\UseCases;

final class SetCaseYear
{
  /**
   * @param array<string, mixed> $data
   * @return array<string, mixed>
   */
    public function handle(array $data): array
    {
        return array_merge($data, [
            'SLFI_YEAR' => (new \DateTimeImmutable())->format('Y'),
        ]);
    }
}
