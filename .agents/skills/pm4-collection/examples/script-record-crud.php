<?php

// Requires docker PHP executor with $api and run_as user token
$collectionId = 2;
$apiInstance = $api->collections();

// Read with PMQL
$records = [];
foreach ($apiInstance->getRecords($collectionId, 'data.status = "active"', 50)->getData() as $record) {
    $records[] = [
        'id' => $record->getId(),
        'name' => $record->getData()['name'] ?? null,
    ];
}

// Create from request data
$newRecord = new \ProcessMaker\Client\Model\RecordsEditable();
$newRecord->setData([
    'name' => $data['applicantName'] ?? 'Unknown',
    'email' => $data['applicantEmail'] ?? '',
    'status' => 'active',
]);
$created = $apiInstance->createRecord($collectionId, $newRecord);

// Partial update (merge)
$patch = new \ProcessMaker\Client\Model\RecordsEditable();
$patch->setData(['status' => 'reviewed']);
$apiInstance->patchRecord($collectionId, $created->getId(), $patch);

return [
    'activeCount' => count($records),
    'createdRecordId' => $created->getId(),
];
