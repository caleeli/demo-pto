<?php

declare(strict_types=1);

namespace Tests\Unit\Projects\SlfiProcess;

use PHPUnit\Framework\TestCase;

final class ScriptWrapperTest extends TestCase
{
    public function testSetCaseYearWrapper(): void
    {
        $data = ['SLFI_YEAR' => '1999'];
        $result = include dirname(__DIR__, 4) . '/projects/slfi-process/scripts/slfi-process-set-case-year.php';

        self::assertIsArray($result);
        self::assertSame((new \DateTimeImmutable())->format('Y'), $result['SLFI_YEAR']);
    }

    public function testCancelCleanupDeletesWhenCancelled(): void
    {
        $repository = new InMemoryInvestorRepository();
        $repository->replaceForApplication('app-9', [['SID_STATUS' => 'Pending']]);

        $data = [
            'application' => 'app-9',
            'SLFI_FLAG_CANCEL_CASE' => 'CANCEL',
        ];
        $investorRepository = $repository;
        $result = include dirname(__DIR__, 4) . '/projects/slfi-process/scripts/slfi-process-cancel-cleanup.php';

        self::assertSame([], $repository->listByApplication('app-9'));
        self::assertSame('CANCEL', $result['SLFI_FLAG_CANCEL_CASE']);
    }
}
