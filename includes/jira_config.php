<?php
/**
 * IntePros Federal Celios.AI CRM - Jira Integration Configuration
 *
 * Credentials are loaded from .env file via env_loader.php
 * This file should be placed in: /includes/jira_config.php
 */

require_once __DIR__ . '/env_loader.php';

// ============================================================================
// JIRA OAUTH 2.0 CONFIGURATION
// ============================================================================

define('JIRA_CLIENT_ID', getenv('JIRA_CLIENT_ID') ?: '');
define('JIRA_CLIENT_SECRET', getenv('JIRA_CLIENT_SECRET') ?: '');

// Your CRM's public URL
define('JIRA_REDIRECT_URI', getenv('JIRA_REDIRECT_URI') ?: 'https://crm.celioscrm.com/jira_oauth_callback.php');

// Atlassian OAuth endpoints
define('JIRA_AUTH_URL', 'https://auth.atlassian.com/authorize');
define('JIRA_TOKEN_URL', 'https://auth.atlassian.com/oauth/token');
define('JIRA_API_BASE', 'https://api.atlassian.com');
define('JIRA_RESOURCES_URL', 'https://api.atlassian.com/oauth/token/accessible-resources');

// OAuth scopes required for the integration
// Using classic scopes only (can't mix with granular scopes)
define('JIRA_SCOPES', implode(' ', [
    'read:jira-work',
    'write:jira-work',
    'read:jira-user',
    'offline_access'
]));

// ============================================================================
// DEFAULT PROJECT CONFIGURATION
// ============================================================================

define('JIRA_DEFAULT_PROJECT_KEY', getenv('JIRA_DEFAULT_PROJECT_KEY') ?: 'EL');
define('JIRA_INSTANCE_URL', getenv('JIRA_INSTANCE_URL') ?: 'https://inteprosfed.atlassian.net');

// ============================================================================
// TOKEN ENCRYPTION
// ============================================================================

define('JIRA_ENCRYPTION_KEY', getenv('JIRA_ENCRYPTION_KEY') ?: '');

// ============================================================================
// CACHE SETTINGS
// ============================================================================

// How long to cache project metadata (in seconds)
define('JIRA_CACHE_TTL', 3600); // 1 hour

// How often to refresh tokens before expiry (in seconds)
define('JIRA_TOKEN_REFRESH_BUFFER', 300); // 5 minutes before expiry

// ============================================================================
// WEBHOOK CONFIGURATION (Optional - for real-time updates)
// ============================================================================

// Set to true if you want to receive webhooks from Jira
define('JIRA_WEBHOOKS_ENABLED', false);

// Secret for validating webhook payloads
define('JIRA_WEBHOOK_SECRET', getenv('JIRA_WEBHOOK_SECRET') ?: '');

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Encrypt a token for secure storage
 */
function jira_encrypt_token($token) {
    $key = hex2bin(JIRA_ENCRYPTION_KEY);
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($token, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypt a stored token
 */
function jira_decrypt_token($encrypted_token) {
    $key = hex2bin(JIRA_ENCRYPTION_KEY);
    $data = base64_decode($encrypted_token);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
}

/**
 * Build the OAuth authorization URL
 */
function jira_get_auth_url($state = null) {
    $params = [
        'audience' => 'api.atlassian.com',
        'client_id' => JIRA_CLIENT_ID,
        'scope' => JIRA_SCOPES,
        'redirect_uri' => JIRA_REDIRECT_URI,
        'state' => $state ?? bin2hex(random_bytes(16)),
        'response_type' => 'code',
        'prompt' => 'consent'
    ];
    
    return JIRA_AUTH_URL . '?' . http_build_query($params);
}

/**
 * Get database connection (uses existing CRM connection)
 */
function jira_get_db() {
    global $conn;
    if (isset($conn) && $conn) {
        return $conn;
    }
    
    // Fallback: create connection using existing config
    require_once __DIR__ . '/config.php';
    return $conn;
}
