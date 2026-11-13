<?php

// Read from request data (set by an upstream screen or task)
$quantity = (float) ($data['quantity'] ?? 0);
$unitPrice = (float) ($data['unitPrice'] ?? 0);

// Return only keys to add or update — merged into request data
return [
    'totalAmount' => $quantity * $unitPrice,
];
