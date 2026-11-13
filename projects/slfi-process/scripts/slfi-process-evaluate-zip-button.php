<?php

/**
 * ProcessMaker script: slfi-process-evaluate-zip-button
 */

use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Application\UseCases\EvaluateZipButton;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\ConfigurationRepositoryPort;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\InvestorRepositoryPort;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Infrastructure\Persistence\RedBeanConfigurationRepository;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Infrastructure\Persistence\RedBeanInvestorRepository;

$configuration = isset($configurationRepository) && $configurationRepository instanceof ConfigurationRepositoryPort
    ? $configurationRepository
    : new RedBeanConfigurationRepository();
$investorRepository = isset($investorRepository) && $investorRepository instanceof InvestorRepositoryPort
    ? $investorRepository
    : new RedBeanInvestorRepository();

return (new EvaluateZipButton($configuration, $investorRepository))->handle($data);
