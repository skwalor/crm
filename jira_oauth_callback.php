<?php
/**
 * IntePros Federal Celios.AI CRM - Jira OAuth Callback Handler
 * 
 * This file handles the OAuth redirect from Atlassian after user authorization.
 * 
 * Place this file in: /volume1/web/crm/jira_oauth_callback.php
 */

ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . '/includes/session_config.php';

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/jira_config.php';
require_once __DIR__ . '/includes/JiraAPI.php';

// Error handling
function redirectWithError($message) {
    error_log('Jira OAuth Error: ' . $message);
    header('Location: index.php?tab=reports&jira_error=' . urlencode($message));
    exit;
}

function redirectWithSuccess($message) {
    header('Location: index.php?tab=reports&jira_success=' . urlencode($message));
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
    
    // Check state - warn but don't fail if user is logged in
    $stateValid = isset($_SESSION['jira_oauth_state']) && $state === $_SESSION['jira_oauth_state'];
    if (!$stateValid) {
        error_log('Jira OAuth WARNING: State mismatch. Expected: ' . ($_SESSION['jira_oauth_state'] ?? 'NOT SET') . ', Got: ' . $state);
    }
    
    // Get user ID - try multiple sources
    $userId = null;
    if (isset($_SESSION['jira_oauth_user_id'])) {
        $userId = $_SESSION['jira_oauth_user_id'];
    } elseif (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        error_log('Jira OAuth: Using session user_id instead of jira_oauth_user_id');
    } else {
        throw new Exception('User session expired - please log in and try again');
    }
    
    // If state doesn't match and user isn't properly logged in, reject
    if (!$stateValid && !isset($_SESSION['loggedin'])) {
        throw new Exception('Invalid state parameter and user not logged in - please try again');
    }
    
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
