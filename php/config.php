<?php
/**
 * Johnny Depp Portfolio - Configuration File
 * Contains email settings and other configuration options
 * Compatible with cPanel hosting environments
 */

// Prevent direct access
if (!defined('PHP_VERSION')) {
    die('Direct access not permitted');
}

// ===== EMAIL CONFIGURATION =====

// Admin email address (where contact form submissions will be sent)
define('ADMIN_EMAIL', 'admin@johnnydeppportfolio.com');

// From email address (should be from your domain for cPanel compatibility)
define('SMTP_FROM_EMAIL', 'noreply@johnnydeppportfolio.com');

// Backup admin email (optional)
define('BACKUP_ADMIN_EMAIL', '');

// ===== SECURITY SETTINGS =====

// Rate limiting - minimum seconds between submissions from same IP
define('RATE_LIMIT_SECONDS', 60);

// Maximum message length (characters)
define('MAX_MESSAGE_LENGTH', 5000);

// Maximum subject length (characters)
define('MAX_SUBJECT_LENGTH', 200);

// Enable logging of form submissions
define('ENABLE_LOGGING', true);

// ===== SPAM PROTECTION =====

// Enable basic spam keyword filtering
define('ENABLE_SPAM_FILTER', true);

// Additional spam keywords (these will be checked in messages)
define('SPAM_KEYWORDS', [
    'viagra', 'casino', 'lottery', 'winner', 'congratulations',
    'million dollars', 'inheritance', 'beneficiary', 'urgent',
    'click here now', 'limited time offer', 'act now'
]);

// ===== VALIDATION SETTINGS =====

// Valid inquiry types
define('VALID_INQUIRY_TYPES', [
    'film-project' => 'Film Project',
    'collaboration' => 'Creative Collaboration', 
    'interview' => 'Interview Request',
    'endorsement' => 'Endorsement Opportunity',
    'event' => 'Event Appearance',
    'other' => 'Other'
]);

// Required form fields
define('REQUIRED_FIELDS', [
    'firstName', 'lastName', 'email', 'inquiryType', 
    'subject', 'message', 'privacy'
]);

// ===== RESPONSE MESSAGES =====

// Success message
define('SUCCESS_MESSAGE', 'Thank you for your message! We will review your inquiry and respond within 5-7 business days.');

// Error messages
define('ERROR_MESSAGES', [
    'invalid_method' => 'Invalid request method. Only POST requests are allowed.',
    'missing_field' => 'Required field is missing or empty.',
    'invalid_email' => 'Please provide a valid email address.',
    'invalid_phone' => 'Please provide a valid phone number or leave it empty.',
    'invalid_inquiry' => 'Please select a valid inquiry type.',
    'spam_detected' => 'Your message appears to contain spam content.',
    'rate_limit' => 'Please wait before submitting another message.',
    'message_too_long' => 'Your message is too long. Please keep it under ' . MAX_MESSAGE_LENGTH . ' characters.',
    'subject_too_long' => 'Your subject is too long. Please keep it under ' . MAX_SUBJECT_LENGTH . ' characters.',
    'email_failed' => 'There was an error processing your request. Please try again or contact us directly.',
    'general_error' => 'An unexpected error occurred. Please try again later.'
]);

// ===== FILE PATHS =====

// Log directory (relative to this config file)
define('LOG_DIR', __DIR__ . '/logs');

// Contact submissions log file
define('CONTACT_LOG_FILE', LOG_DIR . '/contact_submissions.log');

// Newsletter signups log file  
define('NEWSLETTER_LOG_FILE', LOG_DIR . '/newsletter_signups.log');

// Rate limiting data file
define('RATE_LIMIT_FILE', LOG_DIR . '/rate_limit.json');

// Error log file
define('ERROR_LOG_FILE', LOG_DIR . '/errors.log');

// ===== ENVIRONMENT DETECTION =====

// Detect if running on cPanel or similar shared hosting
function isSharedHosting() {
    return (
        isset($_SERVER['DOCUMENT_ROOT']) && 
        (strpos($_SERVER['DOCUMENT_ROOT'], 'public_html') !== false || 
         strpos($_SERVER['DOCUMENT_ROOT'], 'www') !== false)
    );
}

// Get environment type
function getEnvironmentType() {
    if (isSharedHosting()) {
        return 'shared';
    } elseif (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false) {
        return 'apache';
    } else {
        return 'unknown';
    }
}

// ===== UTILITY FUNCTIONS =====

/**
 * Create necessary directories if they don't exist
 */
function ensureDirectoriesExist() {
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
}

/**
 * Get client IP address (works with various proxy configurations)
 */
function getClientIP() {
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
               'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Log error message to file
 */
function logError($message) {
    if (ENABLE_LOGGING) {
        ensureDirectoriesExist();
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}" . PHP_EOL;
        file_put_contents(ERROR_LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Clean old log files (called periodically)
 */
function cleanOldLogs($daysToKeep = 30) {
    $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
    
    $logFiles = [CONTACT_LOG_FILE, NEWSLETTER_LOG_FILE, ERROR_LOG_FILE];
    
    foreach ($logFiles as $logFile) {
        if (file_exists($logFile) && filemtime($logFile) < $cutoffTime) {
            $backupFile = $logFile . '.backup.' . date('Y-m-d');
            rename($logFile, $backupFile);
            touch($logFile);
        }
    }
}

// ===== INITIALIZATION =====

// Ensure required directories exist
ensureDirectoriesExist();

// Set timezone (adjust as needed)
date_default_timezone_set('America/Los_Angeles');

// Set error reporting for development/production
if (getEnvironmentType() === 'shared') {
    // Production environment - hide errors
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ERROR_LOG_FILE);
} else {
    // Development environment - show errors
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Clean old logs occasionally (1% chance)
if (rand(1, 100) === 1) {
    cleanOldLogs();
}

// ===== cPanel SPECIFIC CONFIGURATIONS =====

// Common cPanel PHP settings adjustments
if (isSharedHosting()) {
    // Increase memory limit if allowed
    @ini_set('memory_limit', '256M');
    
    // Set max execution time
    @ini_set('max_execution_time', 30);
    
    // Adjust upload limits
    @ini_set('upload_max_filesize', '10M');
    @ini_set('post_max_size', '10M');
}

// ===== CONSTANTS FOR EMAIL TEMPLATES =====

define('EMAIL_FOOTER', "
---
Johnny Depp Portfolio
Professional Inquiries & Collaborations
Website: [Your Website URL]

This is an automated message. Please do not reply directly to this email.
");

define('AUTO_REPLY_SIGNATURE', "
Best regards,
The Johnny Depp Portfolio Team

" . EMAIL_FOOTER);

// ===== CONFIGURATION VALIDATION =====

// Validate critical configuration
if (!filter_var(ADMIN_EMAIL, FILTER_VALIDATE_EMAIL)) {
    logError('Invalid ADMIN_EMAIL configuration: ' . ADMIN_EMAIL);
}

if (!filter_var(SMTP_FROM_EMAIL, FILTER_VALIDATE_EMAIL)) {
    logError('Invalid SMTP_FROM_EMAIL configuration: ' . SMTP_FROM_EMAIL);
}

// Configuration loaded successfully
define('CONFIG_LOADED', true);
?>
