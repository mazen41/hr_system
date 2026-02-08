<?php
/**
 * Vision HR - API Configuration
 * Centralized config for the API layer
 */

// JWT Secret - CHANGE THIS IN PRODUCTION
define('JWT_SECRET', 'vision-hr-jwt-secret-change-in-production-' . md5(__DIR__));

// JWT Token lifetimes
define('JWT_ACCESS_TTL', 900);       // 15 minutes
define('JWT_REFRESH_TTL', 604800);   // 7 days

// Rate limiting
define('API_RATE_LIMIT', 60);            // requests per window
define('API_RATE_WINDOW', 60);           // window in seconds
define('LOGIN_RATE_LIMIT', 5);           // login attempts per window
define('LOGIN_RATE_WINDOW', 300);        // 5 minutes

// CORS
define('API_CORS_ORIGINS', [
    'http://localhost:5173',   // Vite dev server
    'http://localhost:3000',   // React dev server
    'https://hr.visionsys.net', // Production
]);

// File upload limits
define('API_MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('API_ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);

// GPS anti-spoofing
define('GPS_MAX_ACCURACY', 100);     // Maximum acceptable GPS accuracy in meters
define('QR_CODE_TTL', 60);           // QR code validity in seconds
define('QR_ROTATION_INTERVAL', 30); // QR code rotation interval in seconds

// Firebase Cloud Messaging (FCM) - set your server key for push notifications
define('FCM_SERVER_KEY', ''); // Get from Firebase Console > Project Settings > Cloud Messaging

// Anti-spoofing
define('ANTISPOOF_MAX_RISK', 70);    // Block attendance if risk score >= this value
define('ANTISPOOF_MAX_DEVICES', 3);  // Max devices per user before warning
