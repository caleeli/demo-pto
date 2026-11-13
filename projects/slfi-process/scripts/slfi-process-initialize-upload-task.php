<?php

/**
 * ProcessMaker script: slfi-process-initialize-upload-task
 *
 * BPMN scriptTask: Initialize case (SLFI Process).
 * Logic: InitializeUploadTask.
 */

use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Application\UseCases\InitializeUploadTask;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Domain\Ports\ConfigurationRepositoryPort;
use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Infrastructure\Persistence\RedBeanConfigurationRepository;

$configuration = isset($configurationRepository) && $configurationRepository instanceof ConfigurationRepositoryPort
    ? $configurationRepository
    : new RedBeanConfigurationRepository();

return (new InitializeUploadTask($configuration))->handle($data);
