<?php

declare(strict_types=1);

namespace ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Application\UseCases;

use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\ConfigurationRepositoryPort;

final class InitializeUploadTask
{
    public function __construct(
        private ConfigurationRepositoryPort $configuration
    ) {
    }

  /**
   * @param array<string, mixed> $data
   * @return array<string, mixed>
   */
    public function handle(array $data): array
    {
        $timezone = new \DateTimeZone('America/New_York');
        $now = new \DateTimeImmutable('now', $timezone);

        $days = $this->configuration->getActiveMinExpirationDays();
        $expiration = $now->modify('+' . $days . ' days');

        $userUid = (string) ($data['_user']['id'] ?? $data['userLogged'] ?? $data['USER_LOGGED'] ?? '');
        $firstName = (string) ($data['_user']['firstname'] ?? '');
        $lastName = (string) ($data['_user']['lastname'] ?? '');
        $submitter = trim($firstName . ' ' . $lastName);
        if ($submitter === '' && isset($data['SLFI_SUBMITTER'])) {
            $submitter = (string) $data['SLFI_SUBMITTER'];
        }

        $contact = $this->configuration->getVariableValue('SLFI_CONTACT_INFORMATION') ?? '';

        $baseUrl = (string) ($data['_request']['baseUrl'] ?? '');
        $externalForm = $baseUrl !== ''
            ? rtrim($baseUrl, '/') . '/package/demo-pto/slfi-external-form'
            : '';

        $logoUrl = $baseUrl !== ''
            ? rtrim($baseUrl, '/') . '/vendor/processmaker/demo-pto/resources/images/slfi/northleaf_logo_small.png'
            : '';
        $imgLogo = $logoUrl !== ''
            ? "<img src='" . htmlspecialchars($logoUrl, ENT_QUOTES) . "' style='margin-bottom:10px;width:200px;'>"
            : '';

        return array_merge($data, [
            'SLFI_YEAR' => $now->format('Y'),
            'SLFI_CURRENT_YEAR' => $now->format('Y'),
            'SLFI_CURRENT_DATE' => $now->format('Y-m-d H:i:s'),
            'SLFI_EXPIRATION_DATE' => $expiration->format('Y-m-d H:i:s'),
            'SLFI_MIN_EXPIRATION_DATE' => $expiration->format('Y-m-d'),
            'SLFI_SUBMITTER' => $submitter,
            'SLFI_TAX_USER' => $userUid,
            'SLFI_CONTACT_INFORMATION' => $contact,
            'SLFI_EXTERNAL_FORM' => $externalForm,
            'APP_UID' => (string) ($data['application'] ?? $data['APPLICATION'] ?? ''),
            'CASE_NUMBER' => (string) ($data['case_number'] ?? $data['APP_NUMBER'] ?? ''),
            'TASK_UID' => (string) ($data['task'] ?? $data['TASK'] ?? ''),
            'SLFI_BACK_FORM' => '',
            'SLFI_FLAG_CANCEL_CASE' => '',
            'IMG_LOGO' => $imgLogo,
        ]);
    }
}
