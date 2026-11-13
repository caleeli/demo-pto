<?php

declare(strict_types=1);

namespace ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Infrastructure\Persistence;

use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\InvestorRepositoryPort;

/**
 * Persists investor rows via RedBean when the script executor provides it.
 */
final class RedBeanInvestorRepository implements InvestorRepositoryPort
{
    private const TABLE = 'slfi_investors_data';

  /**
   * @return list<array<string, mixed>>
   */
    public function listByApplication(string $applicationUid): array
    {
        if (!class_exists('\R')) {
            return [];
        }

        $beans = \R::find(self::TABLE, 'app_uid = ? ORDER BY sid_uid ASC', [$applicationUid]);
        $rows = [];
        foreach ($beans as $bean) {
            $rows[] = $this->beanToRow($bean);
        }

        return $rows;
    }

  /**
   * @return list<array<string, mixed>>
   */
    public function listTrackingByApplication(string $applicationUid): array
    {
        $rows = $this->listByApplication($applicationUid);
        foreach ($rows as &$row) {
            $row['SLFI_GRID_INVESTORS_STATUS'] = $row['SID_STATUS'] ?? '';
            $row['SLFI_GRID_INVESTORS_EMAIL'] = $row['SID_EMAIL'] ?? '';
            $row['SLFI_GRID_INVESTORS_DISPLAY_NAME'] = $row['SID_DISPLAY_NAME'] ?? '';
            $row['SLFI_GRID_INVESTORS_INVESTOR_NAME'] = $row['SID_INVESTOR_NAME'] ?? '';
        }
        unset($row);

        return $rows;
    }

    public function deleteByApplication(string $applicationUid): void
    {
        if (!class_exists('\R')) {
            return;
        }
        \R::exec('DELETE FROM ' . self::TABLE . ' WHERE app_uid = ?', [$applicationUid]);
    }

  /**
   * @param list<array<string, mixed>> $rows
   */
    public function replaceForApplication(string $applicationUid, array $rows): void
    {
        if (!class_exists('\R')) {
            return;
        }
        $this->deleteByApplication($applicationUid);
        foreach ($rows as $row) {
            $bean = \R::dispense(self::TABLE);
            $bean->app_uid = $applicationUid;
            $bean->sid_email = $row['SID_EMAIL'] ?? $row['email'] ?? '';
            $bean->sid_display_name = $row['SID_DISPLAY_NAME'] ?? $row['displayName'] ?? '';
            $bean->sid_investor_name = $row['SID_INVESTOR_NAME'] ?? $row['investorName'] ?? '';
            $bean->sid_investor_id = $row['SID_INVESTOR_ID'] ?? $row['investorId'] ?? '';
            $bean->sid_class = $row['SID_CLASS'] ?? $row['class'] ?? '';
            $bean->sid_status = $row['SID_STATUS'] ?? 'Pending';
            \R::store($bean);
        }
    }

    public function countWithStatuses(string $applicationUid, array $statuses): int
    {
        if (!class_exists('\R') || $statuses === []) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $params = array_merge([$applicationUid], $statuses);

        return (int) \R::getCell(
            'SELECT COUNT(*) FROM ' . self::TABLE . ' WHERE app_uid = ? AND sid_status IN (' . $placeholders . ')',
            $params
        );
    }

    public function countCompleted(string $applicationUid): int
    {
        if (!class_exists('\R')) {
            return 0;
        }

        return (int) \R::getCell(
            'SELECT COUNT(*) FROM ' . self::TABLE . ' WHERE app_uid = ? AND sid_status LIKE ?',
            [$applicationUid, 'Completed%']
        );
    }

  /**
   * @param object $bean
   * @return array<string, mixed>
   */
    private function beanToRow($bean): array
    {
        return [
            'SID_UID' => $bean->id ?? null,
            'SID_EMAIL' => $bean->sid_email ?? '',
            'SID_DISPLAY_NAME' => $bean->sid_display_name ?? '',
            'SID_INVESTOR_NAME' => $bean->sid_investor_name ?? '',
            'SID_INVESTOR_ID' => $bean->sid_investor_id ?? '',
            'SID_CLASS' => $bean->sid_class ?? '',
            'SID_STATUS' => $bean->sid_status ?? '',
        ];
    }
}
