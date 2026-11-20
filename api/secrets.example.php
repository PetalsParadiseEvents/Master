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
    // Admin Lead Portal Credentials (REPLACE with strong unique values on your server)
    'ADMIN_USER' => 'admin@petalsparadiseevents.com',
    'ADMIN_PASS' => 'REPLACE_WITH_STRONG_UNIQUE_PASSWORD',
    'ADMIN_SECRET' => 'REPLACE_WITH_RANDOM_32_CHAR_SECRET',

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

    // Gemini AI Chatbot API Key (From https://aistudio.google.com/)
    'GEMINI_API_KEY' => 'YOUR_GEMINI_API_KEY_HERE'
];
