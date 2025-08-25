<?php
/**
 * Johnny Depp Portfolio - Contact Form Handler
 * Processes contact form submissions and sends emails
 * Compatible with cPanel hosting environments
 */

// Include configuration
require_once 'config.php';

// Set content type for JSON response
header('Content-Type: application/json');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Function to send JSON response
function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Function to sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Function to validate phone number (optional field)
function isValidPhone($phone) {
    if (empty($phone)) return true; // Phone is optional
    return preg_match('/^[\+]?[1-9][\d]{0,15}$/', preg_replace('/[^\d\+]/', '', $phone));
}

// Function to log submission
function logSubmission($data) {
    $logFile = __DIR__ . '/logs/contact_submissions.log';
    $logDir = dirname($logFile);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = date('Y-m-d H:i:s') . ' - ' . json_encode($data) . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method. Only POST requests are allowed.');
}

// Check for required fields
$requiredFields = ['firstName', 'lastName', 'email', 'inquiryType', 'subject', 'message', 'privacy'];

foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        sendResponse(false, "Required field '{$field}' is missing or empty.");
    }
}

// Sanitize and validate input data
$firstName = sanitizeInput($_POST['firstName']);
$lastName = sanitizeInput($_POST['lastName']);
$email = sanitizeInput($_POST['email']);
$phone = sanitizeInput($_POST['phone'] ?? '');
$company = sanitizeInput($_POST['company'] ?? '');
$inquiryType = sanitizeInput($_POST['inquiryType']);
$subject = sanitizeInput($_POST['subject']);
$message = sanitizeInput($_POST['message']);
$newsletter = isset($_POST['newsletter']) ? true : false;

// Validate email format
if (!isValidEmail($email)) {
    sendResponse(false, 'Please provide a valid email address.');
}

// Validate phone number if provided
if (!isValidPhone($phone)) {
    sendResponse(false, 'Please provide a valid phone number or leave it empty.');
}

// Validate inquiry type
$validInquiryTypes = ['film-project', 'collaboration', 'interview', 'endorsement', 'event', 'other'];
if (!in_array($inquiryType, $validInquiryTypes)) {
    sendResponse(false, 'Please select a valid inquiry type.');
}

// Basic spam protection
$spamKeywords = ['viagra', 'casino', 'lottery', 'winner', 'congratulations', 'million dollars'];
$messageText = strtolower($message . ' ' . $subject);
foreach ($spamKeywords as $keyword) {
    if (strpos($messageText, $keyword) !== false) {
        sendResponse(false, 'Your message appears to contain spam content.');
    }
}

// Rate limiting (basic implementation)
$rateLimitFile = __DIR__ . '/logs/rate_limit.json';
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$currentTime = time();

if (file_exists($rateLimitFile)) {
    $rateLimitData = json_decode(file_get_contents($rateLimitFile), true);
    if (isset($rateLimitData[$clientIP])) {
        $lastSubmission = $rateLimitData[$clientIP];
        if ($currentTime - $lastSubmission < RATE_LIMIT_SECONDS) {
            sendResponse(false, 'Please wait before submitting another message.');
        }
    }
} else {
    $rateLimitData = [];
}

// Update rate limit data
$rateLimitData[$clientIP] = $currentTime;
file_put_contents($rateLimitFile, json_encode($rateLimitData), LOCK_EX);

// Prepare email content
$inquiryTypeLabels = [
    'film-project' => 'Film Project',
    'collaboration' => 'Creative Collaboration',
    'interview' => 'Interview Request',
    'endorsement' => 'Endorsement Opportunity',
    'event' => 'Event Appearance',
    'other' => 'Other'
];

$inquiryTypeLabel = $inquiryTypeLabels[$inquiryType] ?? 'Unknown';

// Email to admin
$adminEmailSubject = "New Contact Form Submission - {$inquiryTypeLabel}";
$adminEmailBody = "
New contact form submission received from the Johnny Depp Portfolio website.

CONTACT DETAILS:
Name: {$firstName} {$lastName}
Email: {$email}
Phone: " . ($phone ?: 'Not provided') . "
Company: " . ($company ?: 'Not provided') . "

INQUIRY DETAILS:
Type: {$inquiryTypeLabel}
Subject: {$subject}

MESSAGE:
{$message}

ADDITIONAL INFO:
Newsletter Subscription: " . ($newsletter ? 'Yes' : 'No') . "
Submitted: " . date('Y-m-d H:i:s') . "
IP Address: {$clientIP}
User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "
";

// Email headers for admin
$adminHeaders = [
    'From' => SMTP_FROM_EMAIL,
    'Reply-To' => $email,
    'X-Mailer' => 'PHP/' . phpversion(),
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8'
];

$adminHeaderString = '';
foreach ($adminHeaders as $key => $value) {
    $adminHeaderString .= "{$key}: {$value}\r\n";
}

// Auto-reply email to user
$userEmailSubject = "Thank you for contacting Johnny Depp Portfolio";
$userEmailBody = "
Dear {$firstName},

Thank you for reaching out through the Johnny Depp Portfolio website. We have received your message regarding '{$subject}' and appreciate your interest.

Your inquiry details:
- Type: {$inquiryTypeLabel}
- Subject: {$subject}
- Submitted: " . date('F j, Y \a\t g:i A') . "

We aim to respond to all professional inquiries within 5-7 business days. Please note that due to high volume, not all requests can be accommodated.

If your inquiry is urgent, please ensure you have provided all relevant details in your original message.

Thank you for your patience and interest.

Best regards,
Johnny Depp Portfolio Team

---
This is an automated response. Please do not reply to this email.
";

// User email headers
$userHeaders = [
    'From' => SMTP_FROM_EMAIL,
    'X-Mailer' => 'PHP/' . phpversion(),
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8'
];

$userHeaderString = '';
foreach ($userHeaders as $key => $value) {
    $userHeaderString .= "{$key}: {$value}\r\n";
}

// Prepare data for logging
$submissionData = [
    'firstName' => $firstName,
    'lastName' => $lastName,
    'email' => $email,
    'phone' => $phone,
    'company' => $company,
    'inquiryType' => $inquiryType,
    'subject' => $subject,
    'messageLength' => strlen($message),
    'newsletter' => $newsletter,
    'ip' => $clientIP,
    'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
];

try {
    // Send email to admin
    $adminEmailSent = mail(ADMIN_EMAIL, $adminEmailSubject, $adminEmailBody, $adminHeaderString);
    
    if (!$adminEmailSent) {
        throw new Exception('Failed to send admin notification email');
    }
    
    // Send auto-reply to user
    $userEmailSent = mail($email, $userEmailSubject, $userEmailBody, $userHeaderString);
    
    if (!$userEmailSent) {
        // Log warning but don't fail the submission
        error_log("Warning: Failed to send auto-reply email to {$email}");
    }
    
    // Log successful submission
    logSubmission($submissionData);
    
    // Add to newsletter if requested (in a real implementation, this would integrate with your newsletter service)
    if ($newsletter) {
        // Newsletter signup logic would go here
        // For now, just log the newsletter subscription request
        $newsletterLogFile = __DIR__ . '/logs/newsletter_signups.log';
        $newsletterEntry = date('Y-m-d H:i:s') . " - {$email} - {$firstName} {$lastName}" . PHP_EOL;
        file_put_contents($newsletterLogFile, $newsletterEntry, FILE_APPEND | LOCK_EX);
    }
    
    // Send success response
    sendResponse(true, 'Thank you for your message! We will review your inquiry and respond within 5-7 business days.');
    
} catch (Exception $e) {
    // Log error
    error_log("Contact form error: " . $e->getMessage());
    
    // Send error response
    sendResponse(false, 'There was an error processing your request. Please try again or contact us directly.');
}

// Clean up old rate limit entries (once per day)
if (rand(1, 100) === 1) { // 1% chance
    $oneDayAgo = $currentTime - 86400;
    foreach ($rateLimitData as $ip => $timestamp) {
        if ($timestamp < $oneDayAgo) {
            unset($rateLimitData[$ip]);
        }
    }
    file_put_contents($rateLimitFile, json_encode($rateLimitData), LOCK_EX);
}
?>
