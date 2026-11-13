<?php

declare(strict_types=1);

namespace Tests\Unit\Projects\SlfiProcess;

use PHPUnit\Framework\TestCase;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Application\UseCases\EvaluateSubmitFlag;

final class EvaluateSubmitFlagTest extends TestCase
{
    public function testHidesSubmitWhenPendingInvestorsExist(): void
    {
        $repository = new InMemoryInvestorRepository();
        $repository->replaceForApplication('app-1', [
            ['SID_STATUS' => 'Pending'],
        ]);

        $useCase = new EvaluateSubmitFlag($repository);
        $result = $useCase->handle(['application' => 'app-1']);

        self::assertSame('NO', $result['SLFI_FLAG_SUBMIT']);
    }
}
