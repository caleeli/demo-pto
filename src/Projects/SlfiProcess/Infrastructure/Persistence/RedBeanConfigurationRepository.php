<?php

declare(strict_types=1);

namespace ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Infrastructure\Persistence;

use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\ConfigurationRepositoryPort;

final class RedBeanConfigurationRepository implements ConfigurationRepositoryPort
{
    public function getVariableValue(string $variableName): ?string
    {
        if (!class_exists('\R')) {
            return null;
        }
        $bean = \R::findOne('slfi_external_form_variables', 'efv_variable = ?', [$variableName]);

        return $bean ? (string) ($bean->efv_value ?? '') : null;
    }

    public function getActiveMinExpirationDays(): int
    {
        if (!class_exists('\R')) {
            return 0;
        }
        $bean = \R::findOne('slfi_min_expiration_date', 'sme_status = ?', ['ACTIVE']);
        if (!$bean) {
            return 0;
        }

        return (int) ($bean->sme_days ?? 0);
    }

    public function getLatestZipMessageDate(int $caseNumber): ?string
    {
        if (!class_exists('\R')) {
            return null;
        }
        $bean = \R::findOne(
            'slfi_investors_zip_message_history',
            'app_number = ? ORDER BY izm_uid DESC',
            [$caseNumber]
        );

        return $bean ? (string) ($bean->izm_date ?? '') : null;
    }
}
