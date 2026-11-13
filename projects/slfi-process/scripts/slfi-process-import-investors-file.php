<?php

/**
 * ProcessMaker script: slfi-process-import-investors-file
 *
 * Post-upload: parse CSV and populate SLFI_INVESTORS_GRID.
 * Logic: ImportInvestorsFile.
 */

use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Application\UseCases\ImportInvestorsFile;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\InvestorRepositoryPort;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Infrastructure\Persistence\RedBeanInvestorRepository;

$investorRepository = isset($investorRepository) && $investorRepository instanceof InvestorRepositoryPort
    ? $investorRepository
    : new RedBeanInvestorRepository();

return (new ImportInvestorsFile($investorRepository))->handle($data);
