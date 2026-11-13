<?php

declare(strict_types=1);

namespace Tests\Unit\Projects\SlfiProcess;

use PHPUnit\Framework\TestCase;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Application\UseCases\InitializeUploadTask;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\ConfigurationRepositoryPort;

final class InitializeUploadTaskTest extends TestCase
{
    public function testSetsCaseMetadataFromUserAndConfiguration(): void
    {
        $configuration = new class implements ConfigurationRepositoryPort {
            public function getVariableValue(string $variableName): ?string
            {
                return $variableName === 'SLFI_CONTACT_INFORMATION' ? 'tax@example.com' : null;
            }

            public function getActiveMinExpirationDays(): int
            {
                return 30;
            }

            public function getLatestZipMessageDate(int $caseNumber): ?string
            {
                return null;
            }
        };

        $useCase = new InitializeUploadTask($configuration);
        $result = $useCase->handle([
            '_user' => ['id' => 'user-1', 'firstname' => 'Jane', 'lastname' => 'Doe'],
            '_request' => ['baseUrl' => 'https://pm.example.com'],
            'application' => 'app-1',
            'case_number' => '42',
        ]);

        self::assertSame('Jane Doe', $result['SLFI_SUBMITTER']);
        self::assertSame('user-1', $result['SLFI_TAX_USER']);
        self::assertSame('tax@example.com', $result['SLFI_CONTACT_INFORMATION']);
        self::assertSame('app-1', $result['APP_UID']);
        self::assertSame('42', $result['CASE_NUMBER']);
        self::assertNotEmpty($result['SLFI_EXPIRATION_DATE']);
    }
}
