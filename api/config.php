<?php
/**
 * Petals Paradise Events AI Chatbot Config
 *
 * The API key is loaded securely from the server environment variable
 * 'GEMINI_API_KEY'. It is NEVER hardcoded here.
 *
 * HOW TO SET THE ENVIRONMENT VARIABLE:
 * ─────────────────────────────────────
 * Option A — cPanel Hosting:
 *   Go to cPanel → Software → PHP Config → Environment Variables
 *   Add: Name = GEMINI_API_KEY, Value = your_actual_key
 *
 * Option B — .env file (local dev):
 *   Create a .env file in the project root (already in .gitignore) with:
 *   GEMINI_API_KEY=your_actual_key
 *   Then load it with: putenv("GEMINI_API_KEY=your_actual_key") or vlucas/phpdotenv
 *
 * Option C — Direct server injection (VPS/Dedicated):
 *   Add to /etc/environment or your Apache/Nginx vhost config:
 *   SetEnv GEMINI_API_KEY your_actual_key
 *
 * Get a free API Key from: https://aistudio.google.com/
 */

// Read from environment — never hardcoded
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
