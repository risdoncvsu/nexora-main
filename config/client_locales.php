<?php

/*
 * Default locale choices for a client. Timezones are IANA identifiers so PHP,
 * Laravel and PostgreSQL can consistently convert UTC timestamps.
 */
return [
    'countries' => [
        'PH' => ['name' => 'Philippines', 'dial_code' => '+63', 'timezone' => 'Asia/Manila'],
        'SG' => ['name' => 'Singapore', 'dial_code' => '+65', 'timezone' => 'Asia/Singapore'],
        'MY' => ['name' => 'Malaysia', 'dial_code' => '+60', 'timezone' => 'Asia/Kuala_Lumpur'],
        'ID' => ['name' => 'Indonesia', 'dial_code' => '+62', 'timezone' => 'Asia/Jakarta'],
        'TH' => ['name' => 'Thailand', 'dial_code' => '+66', 'timezone' => 'Asia/Bangkok'],
        'VN' => ['name' => 'Vietnam', 'dial_code' => '+84', 'timezone' => 'Asia/Ho_Chi_Minh'],
        'JP' => ['name' => 'Japan', 'dial_code' => '+81', 'timezone' => 'Asia/Tokyo'],
        'KR' => ['name' => 'South Korea', 'dial_code' => '+82', 'timezone' => 'Asia/Seoul'],
        'CN' => ['name' => 'China', 'dial_code' => '+86', 'timezone' => 'Asia/Shanghai'],
        'HK' => ['name' => 'Hong Kong', 'dial_code' => '+852', 'timezone' => 'Asia/Hong_Kong'],
        'IN' => ['name' => 'India', 'dial_code' => '+91', 'timezone' => 'Asia/Kolkata'],
        'AE' => ['name' => 'United Arab Emirates', 'dial_code' => '+971', 'timezone' => 'Asia/Dubai'],
        'SA' => ['name' => 'Saudi Arabia', 'dial_code' => '+966', 'timezone' => 'Asia/Riyadh'],
        'AU' => ['name' => 'Australia', 'dial_code' => '+61', 'timezone' => 'Australia/Sydney'],
        'NZ' => ['name' => 'New Zealand', 'dial_code' => '+64', 'timezone' => 'Pacific/Auckland'],
        'US' => ['name' => 'United States', 'dial_code' => '+1', 'timezone' => 'America/New_York'],
        'CA' => ['name' => 'Canada', 'dial_code' => '+1', 'timezone' => 'America/Toronto'],
        'MX' => ['name' => 'Mexico', 'dial_code' => '+52', 'timezone' => 'America/Mexico_City'],
        'BR' => ['name' => 'Brazil', 'dial_code' => '+55', 'timezone' => 'America/Sao_Paulo'],
        'GB' => ['name' => 'United Kingdom', 'dial_code' => '+44', 'timezone' => 'Europe/London'],
        'DE' => ['name' => 'Germany', 'dial_code' => '+49', 'timezone' => 'Europe/Berlin'],
        'FR' => ['name' => 'France', 'dial_code' => '+33', 'timezone' => 'Europe/Paris'],
        'OTHER' => ['name' => 'Other / not listed', 'dial_code' => '', 'timezone' => 'UTC'],
    ],
];
