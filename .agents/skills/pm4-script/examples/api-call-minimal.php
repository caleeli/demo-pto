<?php

// Magic variables — on script tasks (may be absent in watchers/preview)
$requestId = $data['_request']['id'] ?? null;
$caseNumber = $data['_request']['case_number'] ?? '';

// Injected by PM4 — API_TOKEN belongs to run_as_user_id on the script (not _user in $data)
$apiHost = getenv('API_HOST') ?: '';
$hasApiToken = (getenv('API_TOKEN') ?: '') !== '';

// PM SDK ($api) — PHP docker executor only; uses the same token as getenv('API_TOKEN')
$runAsEmail = '';
if (isset($api) && $hasApiToken) {
    // PM script editor boilerplate — use an id your run_as user can read when testing
    $runAsEmail = $api->users()->getUserById(1)['email'] ?? '';
}

return [
    'requestId' => $requestId,
    'caseNumber' => $caseNumber,
    'apiHost' => $apiHost,
    'hasApiToken' => $hasApiToken,
    'runAsEmail' => $runAsEmail,
];
