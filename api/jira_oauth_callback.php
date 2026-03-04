<?php
/**
 * IntePros Federal Celios.AI CRM - Jira OAuth Callback Handler
 * 
 * This file handles the OAuth redirect from Atlassian after user authorization.
 * 
 * Place this file in: /api/jira_oauth_callback.php
 */

session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/jira_config.php';
require_once __DIR__ . '/../includes/JiraAPI.php';

// Error handling
function redirectWithError($message) {
    header('Location: /?tab=reports&jira_error=' . urlencode($message));
    exit;
}

function redirectWithSuccess($message) {
    header('Location: /?tab=reports&jira_success=' . urlencode($message));
    exit;
}

try {
    // Check for error from Atlassian
    if (isset($_GET['error'])) {
        $error = $_GET['error'];
        $description = $_GET['error_description'] ?? 'Unknown error';
        throw new Exception("OAuth error: {$error} - {$description}");
    }
    
    // Verify required parameters
    if (!isset($_GET['code']) || !isset($_GET['state'])) {
        throw new Exception('Missing authorization code or state parameter');
    }
    
    $code = $_GET['code'];
    $state = $_GET['state'];
    
    // Verify state matches session
    if (!isset($_SESSION['jira_oauth_state']) || $state !== $_SESSION['jira_oauth_state']) {
        throw new Exception('Invalid state parameter - possible CSRF attack');
    }
    
    // Get user ID from session
    if (!isset($_SESSION['jira_oauth_user_id'])) {
        throw new Exception('User session expired - please try again');
    }
    
    $userId = $_SESSION['jira_oauth_user_id'];
    
    // Initialize Jira API and handle callback
    $jira = new JiraAPI($conn, $userId);
    $result = $jira->handleOAuthCallback($code, $state);
    
    if ($result['success']) {
        $siteName = $result['site_name'] ?? 'Jira';
        $userName = $result['user']['displayName'] ?? 'Unknown';
        redirectWithSuccess("Successfully connected to {$siteName} as {$userName}");
    } else {
        throw new Exception('Failed to complete OAuth flow');
    }
    
} catch (Exception $e) {
    error_log('Jira OAuth Error: ' . $e->getMessage());
    redirectWithError($e->getMessage());
}
