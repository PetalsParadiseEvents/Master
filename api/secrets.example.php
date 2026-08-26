<?php
/**
 * Local Server Secrets Configuration Template
 * 
 * Instructions:
 * 1. Copy this file to `api/secrets.php` on your Hostinger server.
 * 2. `api/secrets.php` is ignored by Git (.gitignore), so your DB password and credentials
 *    will NEVER be committed to GitHub public or private repositories.
 */

return [
    // Admin Lead Portal Credentials
    'ADMIN_USER' => 'biragonimounika@gmail.com',
    'ADMIN_PASS' => 'Mounika@65',
    'ADMIN_SECRET' => 'ppe_admin_2026',

    // Hostinger phpMyAdmin MySQL Database Credentials
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'u704222898_ParadiseDB',
    'DB_USER' => 'u704222898_user', // Replace with your Hostinger DB Username
    'DB_PASS' => 'your_db_password', // Replace with your Hostinger DB Password
    'DB_TABLE' => 'leads',

    // Email Notifications List
    'NOTIFICATION_EMAILS' => [
        'contact@petalsparadiseevents.com',
        'biragonimounika@gmail.com'
    ],

    // Gemini AI Chatbot API Key
    'GEMINI_API_KEY' => ''
];
