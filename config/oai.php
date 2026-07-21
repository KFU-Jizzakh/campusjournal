<?php

return [
    'repository_name' => env('OAI_REPOSITORY_NAME', env('APP_NAME', 'Laravel')),
    'repository_id' => env('OAI_REPOSITORY_ID', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost'),
    'admin_email' => env('OAI_ADMIN_EMAIL'),
    'page_size' => (int) env('OAI_PAGE_SIZE', 50),
    'token_ttl_hours' => 24,
];
