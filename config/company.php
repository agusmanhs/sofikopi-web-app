<?php

return [
    // Company identity used across PDF documents (Invoice, Sales Order, Delivery Order).
    'display_name' => env('COMPANY_DISPLAY_NAME', 'SOFIKOPI'),
    'name' => env('COMPANY_NAME', 'PT. SOFIKOPI GROUP INDONESIA'),
    'address' => env('COMPANY_ADDRESS', 'Jalan Andi Djemma, Kanal Selatan 2 No. 82, Makassar, Sulawesi Selatan, 90133, Indonesia'),
    'phone' => env('COMPANY_PHONE', '0816265343'),
    'email' => env('COMPANY_EMAIL', 'sofikopi.id@gmail.com'),

    // NPWP is intentionally a dash until a real value is available (no fake number).
    'npwp' => env('COMPANY_NPWP', '-'),

    // Bank payment defaults (mirror the invoices table column defaults).
    'bank_name' => env('COMPANY_BANK_NAME', 'Bank Mandiri'),
    'bank_account_name' => env('COMPANY_BANK_ACCOUNT_NAME', 'PT. SOFIKOPI GROUP INDONESIA'),
    'bank_account_number' => env('COMPANY_BANK_ACCOUNT_NUMBER', '1740010036036'),
];
