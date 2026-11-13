<?php

/**
 * ProcessMaker script: slfi-process-cancel-cleanup
 */

use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Application\UseCases\CancelCaseCleanup;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\InvestorRepositoryPort;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Infrastructure\Persistence\RedBeanInvestorRepository;

$investorRepository = isset($investorRepository) && $investorRepository instanceof InvestorRepositoryPort
    ? $investorRepository
    : new RedBeanInvestorRepository();

return (new CancelCaseCleanup($investorRepository))->handle($data);
