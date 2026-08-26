<?php
/**
 * Local Server Secrets Configuration Template
 * 
 * Instructions:
 * 1. Copy this file to `api/secrets.php` on your server.
 * 2. `api/secrets.php` is ignored by Git (.gitignore), so your credentials and API keys 
 *    will NEVER be committed to GitHub public or private repositories.
 */

return [
    // Admin Lead Portal Credentials
    'ADMIN_USER' => 'biragonimounika@gmail.com',
    'ADMIN_PASS' => 'Mounika@65',
    'ADMIN_SECRET' => 'ppe_admin_2026',

    // Email Notifications List
    'NOTIFICATION_EMAILS' => [
        'contact@petalsparadiseevents.com',
        'biragonimounika@gmail.com'
    ],

    // Gemini AI Chatbot API Key
    'GEMINI_API_KEY' => ''
];
