<?php

/**
 * ProcessMaker script: slfi-process-load-investors-grid
 */

use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Application\UseCases\LoadInvestorsGrid;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\InvestorRepositoryPort;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Infrastructure\Persistence\RedBeanInvestorRepository;

$investorRepository = isset($investorRepository) && $investorRepository instanceof InvestorRepositoryPort
    ? $investorRepository
    : new RedBeanInvestorRepository();

return (new LoadInvestorsGrid($investorRepository))->handle($data);
