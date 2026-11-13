<?php

/**
 * ProcessMaker script: slfi-process-set-case-year
 */

use ProcessMaker\Package\DemoPto\Projects\SlfiProcess\Application\UseCases\SetCaseYear;

return (new SetCaseYear())->handle($data);
