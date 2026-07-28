<?php

return [
    // Public stores resolve their client from {client}.shop.section4.tech.
    // This must match the wildcard domain configured in DigitalOcean and DNS.
    'storefront_base_domain' => env('ECOMMERCE_STOREFRONT_BASE_DOMAIN', 'shop.section4.tech'),

    // When true, the storefront is also accessible without a subdomain.
    // Useful for local development where wildcard DNS is not available.
    'localhost_fallback' => env('ECOMMERCE_LOCALHOST_FALLBACK', false),
];

