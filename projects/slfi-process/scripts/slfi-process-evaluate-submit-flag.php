<?php

/**
 * ProcessMaker script: slfi-process-evaluate-submit-flag
 */

use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Application\UseCases\EvaluateSubmitFlag;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\InvestorRepositoryPort;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Infrastructure\Persistence\RedBeanInvestorRepository;

$investorRepository = isset($investorRepository) && $investorRepository instanceof InvestorRepositoryPort
    ? $investorRepository
    : new RedBeanInvestorRepository();

return (new EvaluateSubmitFlag($investorRepository))->handle($data);
