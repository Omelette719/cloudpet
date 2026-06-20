<?php

return [
    // Price per CPU unit (server-authoritative)
    'cpu_rate' => env('COMPUTE_CPU_RATE', 4),

    // Price per GB of RAM per hour
    'vram_rate' => env('COMPUTE_VRAM_RATE', 250),

    // Default currency label (informational)
    'currency' => env('COMPUTE_CURRENCY', 'IDR'),
];
