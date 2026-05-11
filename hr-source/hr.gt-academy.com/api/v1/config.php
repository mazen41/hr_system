<?php
/**
 * Vision HR - API Configuration
 * Centralized config for the API layer
 */

// JWT Secret - CHANGE THIS IN PRODUCTION
if (!defined('JWT_SECRET')) define('JWT_SECRET', 'vision-hr-jwt-secret-change-in-production-' . md5(__DIR__));

// JWT Token lifetimes
if (!defined('JWT_ACCESS_TTL')) define('JWT_ACCESS_TTL', 900);       // 15 minutes
if (!defined('JWT_REFRESH_TTL')) define('JWT_REFRESH_TTL', 604800);   // 7 days

// Rate limiting
if (!defined('API_RATE_LIMIT')) define('API_RATE_LIMIT', 60);            // requests per window
if (!defined('API_RATE_WINDOW')) define('API_RATE_WINDOW', 60);           // window in seconds
if (!defined('LOGIN_RATE_LIMIT')) define('LOGIN_RATE_LIMIT', 5);           // login attempts per window
if (!defined('LOGIN_RATE_WINDOW')) define('LOGIN_RATE_WINDOW', 300);        // 5 minutes

// CORS
if (!defined('API_CORS_ORIGINS')) define('API_CORS_ORIGINS', [
    'http://localhost:5173',   // Vite dev server
    'http://localhost:3000',   // React dev server
    'https://hr.visionsys.net', // Production
]);

// File upload limits
if (!defined('API_MAX_UPLOAD_SIZE')) define('API_MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
if (!defined('API_ALLOWED_EXTENSIONS')) define('API_ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);

// GPS anti-spoofing
if (!defined('GPS_MAX_ACCURACY')) define('GPS_MAX_ACCURACY', 100);     // Maximum acceptable GPS accuracy in meters
if (!defined('QR_CODE_TTL')) define('QR_CODE_TTL', 60);           // QR code validity in seconds
if (!defined('QR_ROTATION_INTERVAL')) define('QR_ROTATION_INTERVAL', 30); // QR code rotation interval in seconds

// Firebase Cloud Messaging (FCM) - set your server key for push notifications
if (!defined('FCM_SERVER_KEY')) define('FCM_SERVER_KEY', ''); // Get from Firebase Console > Project Settings > Cloud Messaging

// Anti-spoofing
if (!defined('ANTISPOOF_MAX_RISK')) define('ANTISPOOF_MAX_RISK', 70);    // Block attendance if risk score >= this value
if (!defined('ANTISPOOF_MAX_DEVICES')) define('ANTISPOOF_MAX_DEVICES', 3);  // Max devices per user before warning
