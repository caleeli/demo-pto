<?php

/**
 * ProcessMaker script: slfi-process-load-tracking-grid
 */

use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Application\UseCases\LoadTrackingGrid;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\InvestorRepositoryPort;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Infrastructure\Persistence\RedBeanInvestorRepository;

$investorRepository = isset($investorRepository) && $investorRepository instanceof InvestorRepositoryPort
    ? $investorRepository
    : new RedBeanInvestorRepository();

return (new LoadTrackingGrid($investorRepository))->handle($data);
